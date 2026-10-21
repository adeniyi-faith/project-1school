<?php

namespace App\Services;

use App\Models\PlatformAnnouncement;
use App\Models\SchoolNotification;
use App\Models\User;
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\Mail;
use Throwable;

/** Sends a platform announcement: bell notices and emails to each school's admins and principals. A banner needs no sending; it shows while the notice is live. */
class PlatformAnnouncementService
{
    /** The people who get the bell notice and email: each target school's admins and principals */
    public function recipients(PlatformAnnouncement $a)
    {
        return User::whereIn('school_id', $a->schoolIds())->where('status', 'active')->role(['school-admin', 'principal'])->get(['id', 'school_id', 'name', 'email']);
    }

    public function publish(PlatformAnnouncement $a, User $by): PlatformAnnouncement
    {
        $bell = $mail = $failed = 0;
        $people = ($a->send_bell || $a->send_email) ? $this->recipients($a) : collect();

        if ($a->send_bell) {
            foreach ($people as $u) {
                SchoolNotification::withoutGlobalScopes()->create([
                    'school_id' => $u->school_id, 'user_id' => $u->id, 'type' => 'platform',
                    'title' => $a->title, 'body' => $a->body, 'channel' => 'in-app',
                ]);
                $bell++;
            }
        }

        if ($a->send_email) {
            foreach ($people->filter(fn (User $u) => filled($u->email))->unique('email') as $u) {
                try {
                    Mail::raw($a->body, fn (Message $m) => $m->to($u->email)->subject($a->title));
                    $mail++;
                } catch (Throwable) {
                    $failed++;
                }
            }
        }

        $a->update([
            'status' => 'published', 'published_at' => now(), 'published_by' => $by->id,
            'bell_count' => $bell, 'email_count' => $mail, 'email_failed' => $failed,
        ]);

        return $a;
    }
}
