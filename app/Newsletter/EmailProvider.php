<?php

namespace App\Newsletter;

/**
 * A way of sending email. To add another service (Resend, Amazon SES ...), write one class that does these five
 * things and list it in Providers. Nothing else in the newsletter knows which service is used.
 */
interface EmailProvider
{
    public static function key(): string;

    public static function label(): string;

    /** Plain-English one-liner shown on the settings screen */
    public static function blurb(): string;

    /**
     * What the school must fill in: a list of [key, label, secret (bool, never shown back), kind (text|number|select),
     * options (for select), hint, default]. A provider that needs nothing returns an empty list.
     */
    public static function fields(): array;

    /** Look at the settings without sending anything. Null when they work, otherwise a plain reason. */
    public function check(array $config): ?string;

    /** Send one email. Null when the service accepted it, otherwise a plain reason. */
    public function send(OutgoingEmail $mail, array $config): ?string;
}
