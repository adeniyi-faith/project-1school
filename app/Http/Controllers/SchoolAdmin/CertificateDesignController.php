<?php

namespace App\Http\Controllers\SchoolAdmin;

use App\Http\Controllers\Controller;
use App\Models\CertificateTemplate;
use App\Models\ReportCardDesign;
use App\Models\StudentCertificate;
use App\Services\CertificateService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

/** How testimonials and transfer certificates look and read, with a preview */
class CertificateDesignController extends Controller
{
    public function __construct(private CertificateService $certificates) {}

    public function index(Request $request): Response
    {
        $schoolId = (int) $request->user()->school_id;
        $design = ReportCardDesign::forClass($schoolId, null);

        return Inertia::render('SchoolAdmin/Certificates/Designs', [
            'templates' => collect(array_keys(StudentCertificate::TYPES))->map(fn ($type) => [
                'type' => $type,
                'name' => StudentCertificate::TYPES[$type]['title'],
                ...CertificateTemplate::for($schoolId, $type)->snapshot(),
            ])->values(),
            'defaults' => CertificateTemplate::DEFAULTS,
            'borders' => CertificateTemplate::BORDERS,
            'blanks' => CertificateTemplate::BLANKS,
            'schoolColors' => ['primary' => $design->primary_color ?: '#312e81', 'accent' => $design->accent_color ?: '#4f46e5'],
            'canEdit' => $request->user()->can('settings.edit'),
        ]);
    }

    public function update(Request $request, string $type): RedirectResponse
    {
        abort_unless(isset(StudentCertificate::TYPES[$type]), 404);
        $data = $request->validate([
            'border' => ['required', Rule::in(CertificateTemplate::BORDERS)],
            'primary_color' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'accent_color' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'title' => 'required|string|max:80',
            'body' => 'required|string|max:3000',
            'closing' => 'nullable|string|max:300',
            'show_details' => 'boolean',
            'show_qr' => 'boolean',
            'show_watermark' => 'boolean',
        ], ['primary_color.regex' => 'Choose a colour.', 'accent_color.regex' => 'Choose a colour.', 'body.required' => 'Write the wording of the certificate.']);

        $unknown = array_diff($this->blanksIn($data['body'].' '.($data['closing'] ?? '')), CertificateTemplate::BLANKS);
        if ($unknown) {
            return back()->withErrors(['body' => 'These blanks are not known: '.implode(', ', $unknown).'. Use only the ones listed.']);
        }

        CertificateTemplate::for((int) $request->user()->school_id, $type)->update($data);

        return back()->with('success', StudentCertificate::TYPES[$type]['title'].' design saved. Certificates issued from now on use it; ones already issued keep their look.');
    }

    /** The template printed for a made-up student */
    public function preview(Request $request, string $type): HttpResponse
    {
        abort_unless(isset(StudentCertificate::TYPES[$type]), 404);
        $template = CertificateTemplate::for((int) $request->user()->school_id, $type);
        $bytes = Pdf::loadView('certificates.certificate', $this->certificates->pdfData($this->certificates->sample($template)))->setPaper('a4', 'portrait')->output();

        return response($bytes, 200, ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'inline; filename="certificate-preview.pdf"']);
    }

    private function blanksIn(string $text): array
    {
        preg_match_all('/\{[a-z_]+\}/', $text, $m);

        return array_unique($m[0]);
    }
}
