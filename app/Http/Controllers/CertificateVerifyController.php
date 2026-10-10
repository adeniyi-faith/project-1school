<?php

namespace App\Http\Controllers;

use App\Models\School;
use App\Models\StudentCertificate;
use Illuminate\View\View;

/**
 * The public check for a testimonial or transfer certificate. It shows only what another
 * school needs to trust the paper in front of them: whose it is, which school issued it,
 * when, and whether it has been revoked.
 */
class CertificateVerifyController extends Controller
{
    public function show(string $code): View
    {
        $certificate = StudentCertificate::withoutGlobalScopes()->where('verify_code', strtoupper($code))->first();
        $school = $certificate ? School::find($certificate->school_id) : null;

        return view('certificates.verify', [
            'found' => (bool) $certificate,
            'title' => $certificate?->title(),
            'serial' => $certificate?->serial,
            'name' => $certificate?->details['name'] ?? null,
            'last_class' => $certificate?->details['last_class'] ?? null,
            'date_left' => isset($certificate?->details['date_left']) ? date('j F Y', strtotime($certificate->details['date_left'])) : null,
            'issued_on' => $certificate?->issued_on?->format('j F Y'),
            'school' => $school?->name,
            'revoked' => $certificate?->isRevoked() ?? false,
            'revoked_on' => $certificate?->revoked_at?->format('j F Y'),
        ]);
    }
}
