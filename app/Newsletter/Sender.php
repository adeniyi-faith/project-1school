<?php

namespace App\Newsletter;

/** Who sends a school's newsletter, with what service, and the limits that go with it */
final class Sender
{
    public function __construct(
        public readonly EmailProvider $provider,
        public readonly array $config,
        public readonly string $fromEmail,
        public readonly string $fromName,
        public readonly ?string $replyTo,
        public readonly int $dailyLimit,
        /** 'own' (the school's own account) or 'platform' (the default set by the platform owner) */
        public readonly string $source,
    ) {}
}
