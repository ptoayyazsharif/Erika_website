<?php

namespace App\Services\Ai;

use RuntimeException;

/**
 * A narration attempt that did not produce audio.
 *
 * Carries the provider's own error code alongside a message written for the
 * person waiting, so the job can decide whether retrying is worth anything.
 * Retrying a rate limit is sensible; retrying an empty account is not.
 *
 * The property is `errorCode` rather than `code` because Exception already owns
 * `$code` as a non-readonly int, and PHP will not let a subclass redeclare it.
 */
class NarrationFailed extends RuntimeException
{
    public function __construct(string $message, public readonly string $errorCode = 'unknown')
    {
        parent::__construct($message);
    }

    /** True when another attempt could plausibly succeed. */
    public function worthRetrying(): bool
    {
        return ! in_array($this->errorCode, [
            'quota_exceeded',
            'missing_permissions',
            'unauthorized',
            'authentication_error',
            'invalid_api_key',
            'detected_unusual_activity',
        ], true);
    }
}
