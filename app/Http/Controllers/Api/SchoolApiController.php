<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AdmissionInquiry;
use App\Models\School;
use Illuminate\Http\Request;

/** Read-only data for a school's own scripts. Every call is limited to the school the key belongs to. */
class SchoolApiController extends Controller
{
    public function me(Request $request)
    {
        $school = School::findOrFail($request->attributes->get('school_id'));

        return response()->json(['school' => ['id' => $school->id, 'name' => $school->name, 'slug' => $school->slug]]);
    }

    /** Newest inquiries first. ?since=2026-11-01 only returns newer ones; ?limit= up to 100. */
    public function inquiries(Request $request)
    {
        $data = $request->validate(['since' => 'nullable|date', 'limit' => 'nullable|integer|min:1|max:100', 'status' => 'nullable|string|max:30']);

        $rows = AdmissionInquiry::withoutGlobalScopes()->where('school_id', $request->attributes->get('school_id'))
            ->when($data['since'] ?? null, fn ($q, $d) => $q->where('created_at', '>=', $d))
            ->when($data['status'] ?? null, fn ($q, $s) => $q->where('status', $s))
            ->latest('id')->limit((int) ($data['limit'] ?? 50))->get();

        return response()->json(['inquiries' => $rows->map(fn ($i) => [
            'id' => $i->id, 'reference' => $i->reference, 'status' => $i->status, 'source' => $i->source,
            'student_name' => $i->student_name, 'class_interested' => $i->class_interested,
            'guardian_name' => $i->guardian_name, 'guardian_phone' => $i->guardian_phone, 'guardian_email' => $i->guardian_email,
            'notes' => $i->notes, 'created_at' => $i->created_at?->toIso8601String(),
        ])->values()]);
    }
}
