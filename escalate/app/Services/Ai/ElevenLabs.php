<?php

namespace App\Services\Ai;

use App\Models\AiEvent;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * The only place in this application that talks to ElevenLabs.
 *
 * Returns raw mp3 bytes. The caller is responsible for writing them to the
 * private disk — this class deliberately knows nothing about storage, so there
 * is no path by which it could accidentally write somewhere web-readable.
 */
class ElevenLabs
{
    /** Flash v2.5 bills half a credit per character; ~$0.00003 per char at the
     *  Creator tier. Expressed in microcents per character. */
    private const PRICE_PER_CHAR = 3;

    public function configured(): bool
    {
        return filled(config('escalate.elevenlabs.key'));
    }

    public function narrate(string $text, string $voiceId, ?User $for = null): string
    {
        if (! $this->configured()) {
            throw new RuntimeException('No ElevenLabs API key is configured.');
        }

        $model = config('escalate.voice.model');
        $began = microtime(true);
        $chars = mb_strlen($text);

        try {
            $response = Http::withHeaders([
                'xi-api-key' => config('escalate.elevenlabs.key'),
                'accept'     => 'audio/mpeg',
            ])
                ->timeout(config('escalate.voice.timeout'))
                ->post(config('escalate.elevenlabs.base')."/text-to-speech/{$voiceId}", [
                    'text'     => $text,
                    'model_id' => $model,
                    'output_format' => config('escalate.voice.output_format'),
                    'voice_settings' => [
                        'stability'         => config('escalate.voice.stability'),
                        'similarity_boost'  => config('escalate.voice.similarity'),
                        'style'             => config('escalate.voice.style'),
                        'use_speaker_boost' => true,
                        'speed'             => config('escalate.voice.speed'),
                    ],
                ]);
        } catch (\Throwable $e) {
            $this->record($for, $model, false, 'transport', $began, $chars);

            throw new RuntimeException('Could not reach the narration service.', 0, $e);
        }

        if (! $response->successful()) {
            $code = (string) $response->status();
            $this->record($for, $model, false, $code, $began, $chars);

            throw new RuntimeException("The narration service refused the request ({$code}).");
        }

        $bytes = $response->body();

        // A short body is an error page with a 200, or a truncated stream.
        // Either way it is not audio, and caching it would be worse than
        // failing — the user would get silence with no way to retry.
        if (strlen($bytes) < 2048) {
            $this->record($for, $model, false, 'short_body', $began, $chars);

            throw new RuntimeException('The narration service returned no audio.');
        }

        $this->record($for, $model, true, null, $began, $chars);

        return $bytes;
    }

    private function record(?User $for, string $model, bool $ok, ?string $error, float $began, int $chars): void
    {
        AiEvent::create([
            'user_id'         => $for?->id,
            'kind'            => 'narration',
            'provider'        => 'elevenlabs',
            'model'           => $model,
            'ok'              => $ok,
            'error_code'      => $error,
            'characters'      => $chars,
            'duration_ms'     => (int) round((microtime(true) - $began) * 1000),
            'cost_microcents' => $ok ? $chars * self::PRICE_PER_CHAR : 0,
        ]);
    }

    /**
     * Duration of an mp3, read from its frame headers.
     *
     * Needed so the player can show a real length and the reveal can pace text
     * against the audio, without shelling out to ffprobe (which shared hosting
     * will not have). Only handles the constant-bitrate MPEG-1 Layer III that
     * mp3_44100_128 produces — anything else returns null and the UI falls back
     * to a word-count estimate.
     */
    public function durationMs(string $bytes): ?int
    {
        $offset = 0;
        $len = strlen($bytes);

        // Skip an ID3v2 tag if present.
        if ($len > 10 && substr($bytes, 0, 3) === 'ID3') {
            $size = (ord($bytes[6]) << 21) | (ord($bytes[7]) << 14)
                  | (ord($bytes[8]) << 7)  | ord($bytes[9]);
            $offset = 10 + $size;
        }

        // Find the first frame sync and read its bitrate.
        for ($i = $offset; $i < min($len - 4, $offset + 8192); $i++) {
            if (ord($bytes[$i]) !== 0xFF || (ord($bytes[$i + 1]) & 0xE0) !== 0xE0) {
                continue;
            }

            $version = (ord($bytes[$i + 1]) >> 3) & 0x03;   // 3 = MPEG-1
            $layer   = (ord($bytes[$i + 1]) >> 1) & 0x03;   // 1 = Layer III
            $rateIdx = (ord($bytes[$i + 2]) >> 4) & 0x0F;

            if ($version !== 3 || $layer !== 1 || $rateIdx === 0 || $rateIdx === 0x0F) {
                continue;
            }

            $rates = [0, 32, 40, 48, 56, 64, 80, 96, 112, 128, 160, 192, 224, 256, 320];
            $kbps = $rates[$rateIdx];

            return (int) round((($len - $offset) * 8) / $kbps);
        }

        return null;
    }
}
