<?php

namespace App\Support;

use Illuminate\Http\Request;

/** One place where the platform owner's actions are written to the audit trail (log name "platform"), with who, from where and why */
class PlatformAudit
{
    public static function record(string $what, array $details = [], ?string $reason = null, ?Request $request = null): void
    {
        $request ??= request();

        activity('platform')
            ->causedBy($request->user())
            ->withProperties(array_filter([
                ...$details,
                'reason' => $reason,
                'ip' => $request->ip(),
                'acting_school_id' => ActingSchool::id(),
            ], fn ($v) => $v !== null && $v !== ''))
            ->log($what);
    }
}
