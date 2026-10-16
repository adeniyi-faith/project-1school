<?php

namespace App\Newsletter;

use Illuminate\Mail\Message;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\HtmlString;
use Throwable;

/** The platform's own mail account (the cPanel mailbox the app already sends its other emails with). Needs no settings. */
class MailboxProvider implements EmailProvider
{
    public static function key(): string { return 'mailbox'; }

    public static function label(): string { return 'The platform\'s mailbox'; }

    public static function blurb(): string { return 'The same mail account the system already uses. Fine for small lists, but shared hosting limits how many emails it can send and large sends may land in spam.'; }

    public static function fields(): array { return []; }

    public function check(array $config): ?string
    {
        return null;
    }

    public function send(OutgoingEmail $mail, array $config): ?string
    {
        try {
            Mail::send([], [], function (Message $m) use ($mail) {
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
