<?php

namespace App\Http\Controllers;

use App\Models\School;
use App\Models\StudentIdCard;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * What a student ID card's QR code opens. Signed-in staff of the card's school go straight to the scan
 * screen (photo, attendance, fees). Anyone else sees only whether the card is genuine and still valid,
 * with the name, class and school already printed on the card.
 */
class IdCardCheckController extends Controller
{
    public function __invoke(Request $request, string $token): Response|RedirectResponse
    {
        $card = StudentIdCard::withoutGlobalScopes()->with(['student' => fn ($q) => $q->withoutGlobalScopes()->with(['schoolClass' => fn ($c) => $c->withoutGlobalScopes()])])
            ->where('token', $token)->first();
        $user = $request->user();
        if ($card && $user && $user->school_id === $card->school_id && $user->can('students.view')) {
            return redirect()->route('school.id-cards.scan', ['code' => $token]);
        }

        return response()->view('id-cards.check', [
            'found' => (bool) $card,
            'state' => $card?->state(),
            'name' => $card?->student?->full_name,
            'class' => $card?->student?->schoolClass?->name,
            'school' => $card ? School::find($card->school_id)?->name : null,
            'card_no' => $card?->card_no,
            'expires_on' => $card?->expires_on?->format('j F Y'),
        ], $card ? 200 : 404);
    }
}
