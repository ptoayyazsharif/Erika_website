<?php

namespace App\Services\Ai;

use App\Models\AiEvent;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * The only place in this application that talks to Anthropic.
 *
 * Everything that wants generated text goes through here, which means the API
 * key is read in exactly one file and every call is written to the ai_events
 * ledger whether it succeeded or not. That ledger is what quotas count and
 * what the admin area reports on — no user content in it, only what was
 * called and what it cost.
 */
class Anthropic
{
    /** Sonnet pricing, in microcents per token, so integers stay exact. */
    private const PRICE = [
        'claude-opus-5'      => ['in' => 500,  'out' => 2500],
        'claude-sonnet-5'    => ['in' => 300,  'out' => 1500],
        'claude-haiku-4-5'   => ['in' => 100,  'out' => 500],
    ];

    public function configured(): bool
    {
        return filled(config('escalate.anthropic.key'));
    }

    /**
     * Send a system prompt and a user prompt, get text back.
     *
     * @throws RuntimeException when the provider is unreachable or refuses.
     */
    public function write(string $system, string $user, ?User $for = null, string $kind = 'story'): string
    {
        if (! $this->configured()) {
            throw new RuntimeException('No Anthropic API key is configured.');
        }

        $model = config('escalate.story.model');
        $began = microtime(true);

        try {
            $response = Http::withHeaders([
                'x-api-key'         => config('escalate.anthropic.key'),
                'anthropic-version' => config('escalate.anthropic.version'),
                'content-type'      => 'application/json',
            ])
                ->timeout(config('escalate.story.timeout'))
                ->retry(2, 1500, throw: false)
                ->post(config('escalate.anthropic.base').'/messages', [
                    'model'      => $model,
                    'max_tokens' => config('escalate.story.max_tokens'),
                    'system'     => $system,
                    'messages'   => [['role' => 'user', 'content' => $user]],
                ]);
        } catch (\Throwable $e) {
            $this->record($for, $kind, $model, false, 'transport', $began);

            throw new RuntimeException('Could not reach the writing service.', 0, $e);
        }

        if (! $response->successful()) {
            $code = (string) ($response->json('error.type') ?? $response->status());
            $this->record($for, $kind, $model, false, $code, $began);

            throw new RuntimeException("The writing service refused the request ({$code}).");
        }

        $text = collect($response->json('content') ?? [])
            ->where('type', 'text')
            ->pluck('text')
            ->implode('');

        $in  = (int) $response->json('usage.input_tokens', 0);
        $out = (int) $response->json('usage.output_tokens', 0);

        $this->record($for, $kind, $model, true, null, $began, $in, $out);

        if (trim($text) === '') {
            throw new RuntimeException('The writing service returned nothing.');
        }

        return trim($text);
    }

    private function record(
        ?User $for, string $kind, string $model, bool $ok, ?string $error,
        float $began, int $in = 0, int $out = 0,
    ): void {
        $price = self::PRICE[$model] ?? self::PRICE['claude-sonnet-5'];

        AiEvent::create([
            'user_id'         => $for?->id,
            'kind'            => $kind,
            'provider'        => 'anthropic',
            'model'           => $model,
            'ok'              => $ok,
            'error_code'      => $error,
            'input_tokens'    => $in ?: null,
            'output_tokens'   => $out ?: null,
            'duration_ms'     => (int) round((microtime(true) - $began) * 1000),
            'cost_microcents' => (int) round(($in * $price['in'] + $out * $price['out']) / 1000),
        ]);
    }
}
