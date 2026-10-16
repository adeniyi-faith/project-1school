<?php

namespace App\Newsletter;

use Illuminate\Support\Facades\Http;
use Throwable;

/** Brevo (formerly Sendinblue): sends through its web service, with a free allowance of emails every day */
class BrevoProvider implements EmailProvider
{
    private const API = 'https://api.brevo.com/v3';

    public static function key(): string { return 'brevo'; }

    public static function label(): string { return 'Brevo'; }

    public static function blurb(): string { return 'Recommended. A sending service built for newsletters, with a free daily allowance. Your emails reach inboxes more reliably than from a normal mailbox.'; }

    public static function fields(): array
    {
        return [['key' => 'api_key', 'label' => 'Brevo API key', 'kind' => 'password', 'secret' => true, 'hint' => 'In Brevo: your name (top right) > SMTP & API > API keys > Generate a new API key. Also add and verify your sending email under Senders.']];
    }

    public function check(array $config): ?string
    {
        $key = trim((string) ($config['api_key'] ?? ''));
        if ($key === '') {
            return 'Paste your Brevo API key.';
        }
        try {
            $res = Http::withHeaders(['api-key' => $key, 'accept' => 'application/json'])->timeout(15)->get(self::API.'/account');
        } catch (Throwable) {
            return 'Could not reach Brevo. Please try again.';
        }

        return $res->successful() ? null : ($res->status() === 401 ? 'Brevo did not accept that key. Copy it again from Brevo.' : 'Brevo answered with an error ('.$res->status().').');
    }

    public function send(OutgoingEmail $mail, array $config): ?string
    {
        $body = [
            'sender' => ['email' => $mail->fromEmail, 'name' => $mail->fromName],
            'to' => [['email' => $mail->toEmail] + ($mail->toName ? ['name' => $mail->toName] : [])],
            'subject' => $mail->subject, 'htmlContent' => $mail->html, 'textContent' => $mail->text,
        ];
        if ($mail->replyTo) {
            $body['replyTo'] = ['email' => $mail->replyTo];
        }
        if ($mail->unsubscribeUrl) {
            $body['headers'] = ['List-Unsubscribe' => '<'.$mail->unsubscribeUrl.'>', 'List-Unsubscribe-Post' => 'List-Unsubscribe=One-Click'];
        }
        try {
            $res = Http::withHeaders(['api-key' => (string) ($config['api_key'] ?? ''), 'accept' => 'application/json'])->timeout(20)->post(self::API.'/smtp/email', $body);
        } catch (Throwable) {
            return 'Could not reach Brevo.';
        }
        if ($res->successful()) {
            return null;
        }

        return 'Brevo refused it: '.mb_substr((string) ($res->json('message') ?? $res->status()), 0, 200);
    }
}
