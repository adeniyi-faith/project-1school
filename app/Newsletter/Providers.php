<?php

namespace App\Newsletter;

/** Every way of sending the newsletter can use. Add a new service by adding its class here. */
class Providers
{
    /** Services a school can choose for itself (the platform mailbox is only the platform's default) */
    public const SCHOOL = [BrevoProvider::class, SmtpProvider::class];

    public static function all(): array
    {
        $out = [];
        foreach ([MailboxProvider::class, BrevoProvider::class, SmtpProvider::class] as $class) {
            $out[$class::key()] = $class;
        }

        return $out;
    }

    public static function make(string $key): ?EmailProvider
    {
        $class = self::all()[$key] ?? null;

        return $class ? new $class : null;
    }

    public static function describe(string $class): array
    {
        return ['key' => $class::key(), 'label' => $class::label(), 'blurb' => $class::blurb(), 'fields' => $class::fields()];
    }
}
