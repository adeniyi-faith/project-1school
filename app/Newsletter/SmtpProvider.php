<?php

namespace App\Newsletter;

use App\Services\WebhookService;
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\Mail;
use Throwable;

/** Any mailbox or service that speaks SMTP: a school's own email account, Gmail, Zoho, Resend, Amazon SES, Mailgun ... */
class SmtpProvider implements EmailProvider
{
    public static function key(): string { return 'smtp'; }

    public static function label(): string { return 'My own mailbox or another service (SMTP)'; }

    public static function blurb(): string { return 'Works with any email account or sending service that gives you SMTP details: your school\'s own mailbox, Zoho, Resend, Amazon SES, Mailgun and more.'; }

    public static function fields(): array
    {
        return [
            ['key' => 'host', 'label' => 'Server (host)', 'kind' => 'text', 'secret' => false, 'hint' => 'For example smtp.zoho.com'],
            ['key' => 'port', 'label' => 'Port', 'kind' => 'number', 'secret' => false, 'default' => '587', 'hint' => 'Usually 587 (or 465 with SSL)'],
            ['key' => 'encryption', 'label' => 'Security', 'kind' => 'select', 'secret' => false, 'options' => ['tls' => 'TLS (port 587)', 'ssl' => 'SSL (port 465)'], 'default' => 'tls'],
            ['key' => 'username', 'label' => 'Username', 'kind' => 'text', 'secret' => false],
            ['key' => 'password', 'label' => 'Password', 'kind' => 'password', 'secret' => true, 'hint' => 'Kept safe and never shown again'],
        ];
    }

    private function transportConfig(array $c): array
    {
        return [
            'transport' => 'smtp', 'host' => (string) ($c['host'] ?? ''), 'port' => (int) ($c['port'] ?? 587),
            'username' => (string) ($c['username'] ?? ''), 'password' => (string) ($c['password'] ?? ''),
            'scheme' => ($c['encryption'] ?? 'tls') === 'ssl' ? 'smtps' : 'smtp', 'timeout' => 15,
        ];
    }

    public function check(array $config): ?string
    {
        $host = trim((string) ($config['host'] ?? ''));
        if ($host === '' || ! preg_match('/^[A-Za-z0-9.-]+$/', $host)) {
            return 'Enter the server name, for example smtp.zoho.com.';
        }
        if ($problem = WebhookService::urlProblem('https://'.$host)) {
            return 'That server cannot be used: '.$problem;
        }
        if (($config['username'] ?? '') === '' || ($config['password'] ?? '') === '') {
            return 'Enter the username and password.';
        }
        try {
            $transport = Mail::build($this->transportConfig($config))->getSymfonyTransport();
            $transport->start();
            $transport->stop();

            return null;
        } catch (Throwable $e) {
            return 'Could not sign in to that server: '.mb_substr($e->getMessage(), 0, 160);
        }
    }

    public function send(OutgoingEmail $mail, array $config): ?string
    {
        try {
            Mail::build($this->transportConfig($config))->send([], [], function (Message $m) use ($mail) {
                $m->to($mail->toEmail, $mail->toName)->from($mail->fromEmail, $mail->fromName)->subject($mail->subject)->html($mail->html)->text($mail->text);
                if ($mail->replyTo) {
                    $m->replyTo($mail->replyTo);
                }
                if ($mail->unsubscribeUrl) {
                    $h = $m->getSymfonyMessage()->getHeaders();
                    $h->addTextHeader('List-Unsubscribe', '<'.$mail->unsubscribeUrl.'>');
                    $h->addTextHeader('List-Unsubscribe-Post', 'List-Unsubscribe=One-Click');
                }
            });

            return null;
        } catch (Throwable $e) {
            return 'The mail server refused it: '.mb_substr($e->getMessage(), 0, 200);
        }
    }
}
