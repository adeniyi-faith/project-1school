<?php

namespace App\Http\Controllers\SchoolAdmin;

use App\Http\Controllers\Controller;
use App\Models\CertificateTemplate;
use App\Models\ReportCardDesign;
use App\Models\StudentCertificate;
use App\Services\CertificateService;
use App\Support\EmbeddedImage;
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

    /**
     * The sample certificate shown on screen with whatever is in the form right now, including a logo,
     * stamp or signature picture just chosen. Nothing is saved or stored, so the demo school can use it too.
     */
    public function tryOut(Request $request, string $type): HttpResponse
    {
        abort_unless(isset(StudentCertificate::TYPES[$type]), 404);
        $image = 'nullable|image|mimes:png,jpg,jpeg|max:2048';
        $data = $request->validate([
            'border' => ['nullable', Rule::in(CertificateTemplate::BORDERS)],
            'primary_color' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'accent_color' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'title' => 'nullable|string|max:80',
            'body' => 'nullable|string|max:3000',
            'closing' => 'nullable|string|max:300',
            'show_details' => 'boolean',
            'show_qr' => 'boolean',
            'show_watermark' => 'boolean',
            'school_logo' => $image,
            'stamp' => $image,
            'signature' => $image,
        ]);

        $unknown = array_diff($this->blanksIn(($data['body'] ?? '').' '.($data['closing'] ?? '')), CertificateTemplate::BLANKS);
        if ($unknown) {
            return response()->json(['message' => 'These blanks are not known: '.implode(', ', $unknown), 'errors' => ['body' => ['These blanks are not known: '.implode(', ', $unknown).'. Use only the ones listed.']]], 422);
        }

        $template = CertificateTemplate::for((int) $request->user()->school_id, $type);
        $template->fill(array_filter(collect($data)->only(['border', 'primary_color', 'accent_color', 'title', 'body', 'closing', 'show_details', 'show_qr', 'show_watermark'])->all(), fn ($v) => $v !== null));
        // an empty colour box means "use the report card colour"
        foreach (['primary_color', 'accent_color'] as $key) {
            if ($request->exists($key) && ! $request->filled($key)) {
                $template->{$key} = null;
            }
        }

        $view = $this->certificates->pdfData($this->certificates->sample($template));
        if ($logo = EmbeddedImage::fromUpload($request->file('school_logo'))) {
            $view['school']['logo'] = $logo;
        }
        if ($stamp = EmbeddedImage::fromUpload($request->file('stamp'))) {
            $view['stamp'] = $stamp;
        }
        if ($signature = EmbeddedImage::fromUpload($request->file('signature'))) {
            $view['signer'] = $view['signer'] ?? ['label' => 'Principal', 'name' => null];
            $view['signer']['signature'] = $signature;
        }

        // The PDF paints its borders on the page edge ("fixed"); on screen they belong to a sheet instead
        $html = view('certificates.certificate', $view)->render();
        $html = str_replace('</style>', '.page{position:relative;box-sizing:border-box;width:794px;min-height:1123px;padding:22px;background:#fff;box-shadow:0 1px 4px rgba(0,0,0,.18);overflow:hidden}.pg{position:relative;min-height:1079px}.pg .b,.pg .watermark,.pg .revoked,.pg .foot{position:absolute!important}.pg .foot{left:48px;right:48px}</style>', $html);
        $html = preg_replace('/<body[^>]*>/', '$0<div class="page"><div class="pg">', $html, 1);
        $html = str_replace('</body>', '</div></div></body>', $html);

        return ReportCardController::screenPage($html, 'Preview '.StudentCertificate::TYPES[$type]['title'], false, 794);
    }

    private function blanksIn(string $text): array
    {
        preg_match_all('/\{[a-z_]+\}/', $text, $m);

        return array_unique($m[0]);
    }
}
