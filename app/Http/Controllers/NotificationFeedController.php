<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\Guardian;
use App\Models\SchoolNotification;
use App\Models\Student;
use Illuminate\Http\JsonResponse;

/**
 * The little list that opens when someone taps the bell at the top of any screen.
 * It only reads, so it also works for the read-only demo accounts.
 */
class NotificationFeedController extends Controller
{
    public function index(): JsonResponse
    {
        $user  = auth()->user();
        $roles = $user->getRoleNames();
        $items = collect();
        $unread = 0;

        $announcements = Announcement::where('school_id', $user->school_id)->published();

        if ($roles->contains('student')) {
            $classId = Student::where('school_id', $user->school_id)->where('user_id', $user->id)->value('class_id');
            $announcements->for('student', [$classId]);
        } elseif ($roles->contains('parent')) {
            $classIds = Student::where('school_id', $user->school_id)
                ->whereIn('guardian_id', Guardian::where('school_id', $user->school_id)->where('user_id', $user->id)->pluck('id'))
                ->pluck('class_id')->filter()->all();
            $announcements->for('parent', $classIds);
        } else {
            $announcements->where(fn ($q) => $q->where('audience', 'all')
                ->orWhere(fn ($r) => $r->where('audience', 'role')->whereIn('target_role', $roles)));

            $mine = SchoolNotification::where('user_id', $user->id)->latest()->limit(15)->get();
            $unread = SchoolNotification::where('user_id', $user->id)->whereNull('read_at')->count();
            foreach ($mine as $n) {
                $items->push([
                    'key' => 'n' . $n->id, 'title' => $n->title, 'body' => $n->body,
                    'when' => $n->created_at, 'unread' => $n->read_at === null, 'kind' => 'Notification',
                    'url' => is_array($n->data) ? ($n->data['url'] ?? null) : null,
                ]);
            }
        }

        foreach ($announcements->orderByDesc('is_pinned')->orderByDesc('published_at')->limit(15)->get() as $a) {
            $items->push([
                'key' => 'a' . $a->id, 'title' => $a->title, 'body' => $a->body,
                'when' => $a->published_at ?? $a->created_at, 'unread' => false, 'kind' => 'Announcement',
            ]);
        }

        return response()->json([
            'unread' => $unread,
            'items'  => $items->sortByDesc('when')->take(15)->values()->map(fn ($i) => [
                ...$i,
                'when' => $i['when']?->diffForHumans(),
            ]),
        ]);
    }
}
