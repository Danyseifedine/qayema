<?php

namespace App\Services\Push;

/**
 * A notification for a phone: what it says, and what the app opens when
 * it is tapped (`data`, string values only, as Firebase requires).
 */
final readonly class PushMessage
{
    /**
     * @param  array<string, string>  $data
     */
    public function __construct(
        public string $title,
        public string $body,
        public array $data = [],
    ) {}
}
