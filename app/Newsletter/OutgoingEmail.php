<?php

namespace App\Newsletter;

/** One email, ready to hand to any sending service */
final class OutgoingEmail
{
    public function __construct(
        public readonly string $toEmail,
        public readonly ?string $toName,
        public readonly string $fromEmail,
        public readonly string $fromName,
        public readonly ?string $replyTo,
        public readonly string $subject,
        public readonly string $html,
        public readonly string $text,
        /** The one-click unsubscribe address, sent as a header too so mail apps can show their own "Unsubscribe" button */
        public readonly ?string $unsubscribeUrl = null,
    ) {}
}
