<?php
/**
 * Google Gemini wrapper.
 * Gemini 3.x rules honored here:
 *   - never sets temperature / top_p / top_k (3.x is tuned for defaults)
 *   - uses thinking_level (thinkingConfig.thinkingLevel), not thinking_budget
 *   - forces structured output with responseMimeType + responseSchema
 *   - tries the primary model, falls back to the "-latest" alias on 404, and logs it
 * The API key is read from config only and is NEVER logged.
 */
require_once __DIR__ . '/db.php';

const GEMINI_ENDPOINT = 'https://generativelanguage.googleapis.com/v1beta/models/%s:generateContent?key=%s';

function gemini_log(string $msg): void {
    @file_put_contents(__DIR__ . '/gemini.log',
        '[' . date('Y-m-d H:i:s') . '] ' . $msg . "\n", FILE_APPEND);
}

/**
 * Structured JSON call with model fallback.
 * @return array{0:bool,1:?array,2:string,3:string}  [ok, data, error, modelUsed]
 */
function gemini_json(string $role, string $systemText, string $userText,
                     array $responseSchema, ?string $thinkingLevel = null): array {
    $c = cfg();
    $key = $c['gemini_key'] ?? '';
    $models = $c['models'][$role] ?? [];
    if ($key === '' || $key === 'YOUR_GEMINI_API_KEY' || !$models) {
        return [false, null, "No API key or models configured for role '$role'.", ''];
    }

    $genConfig = [
        'responseMimeType' => 'application/json',
        'responseSchema'   => $responseSchema,
    ];
    if ($thinkingLevel !== null) {
        $genConfig['thinkingConfig'] = ['thinkingLevel' => $thinkingLevel];
    }
    // Deliberately NO temperature / topP / topK.

    $payload = [
        'systemInstruction' => ['parts' => [['text' => $systemText]]],
        'contents'          => [['role' => 'user', 'parts' => [['text' => $userText]]]],
        'generationConfig'  => $genConfig,
    ];
    $body = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

    $lastErr = '';
    foreach ($models as $i => $model) {
        [$code, $resp, $curlErr] = gemini_http($model, $key, $body);

        if ($curlErr !== '') { $lastErr = "cURL: $curlErr"; gemini_log("cURL error model=$model role=$role: $curlErr"); continue; }

        if ($code === 404) {
            $lastErr = "404 for model '$model'";
            gemini_log("Model 404: '$model' (role=$role) — falling back to next alias.");
            continue;
        }
        if ($code < 200 || $code >= 300) {
            $lastErr = "HTTP $code: " . substr($resp, 0, 300);
            gemini_log("Model '$model' role=$role HTTP $code: " . substr($resp, 0, 200));
            continue;
        }

        $j = json_decode($resp, true);
        $text = $j['candidates'][0]['content']['parts'][0]['text'] ?? null;
        if ($text === null) {
            $reason = $j['promptFeedback']['blockReason']
                    ?? ($j['candidates'][0]['finishReason'] ?? 'no text returned');
            $lastErr = "No content ($reason)";
            gemini_log("Model '$model' role=$role returned no text: $reason");
            continue;
        }

        $data = json_decode($text, true);
        if (!is_array($data)) {
            $lastErr = 'Model output was not valid JSON';
            gemini_log("Model '$model' role=$role invalid JSON: " . substr($text, 0, 200));
            continue;
        }

        if ($i > 0) gemini_log("Fallback model '$model' succeeded for role=$role.");
        return [true, $data, '', $model];
    }
    return [false, null, $lastErr, ''];
}

/** @return array{0:int,1:string,2:string}  [httpCode, responseBody, curlError] */
function gemini_http(string $model, string $key, string $body): array {
    $url = sprintf(GEMINI_ENDPOINT, rawurlencode($model), rawurlencode($key));
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $body,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT        => 180,
    ]);
    $resp = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err  = curl_error($ch);
    curl_close($ch);
    return [$code, (string) $resp, $err];
}
