<?php

namespace App\Http\Controllers\SchoolAdmin;

use App\Http\Controllers\Controller;
use App\Models\ReportCardSigner;
use App\Models\Student;
use App\Models\StudentCertificate;
use App\Services\CertificateService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

/**
 * Testimonials and transfer certificates: the register of everything issued, the issue form
 * (filled in from the student's record), the PDF, and revoking a wrong one.
 */
class CertificateController extends Controller
{
    public function __construct(private CertificateService $certificates) {}

    public function index(Request $request): Response
    {
        $type = in_array($request->type, array_keys(StudentCertificate::TYPES), true) ? $request->type : null;
        $search = trim((string) $request->search);

        $rows = StudentCertificate::with(['student:id,first_name,last_name,admission_no', 'issuer:id,name'])
            ->when($type, fn ($q) => $q->where('type', $type))
            ->when($search !== '', fn ($q) => $q->where(fn ($q) => $q
                ->where('serial', 'like', "%{$search}%")
                ->orWhereHas('student', fn ($s) => $s->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")->orWhere('admission_no', 'like', "%{$search}%"))))
            ->latest('id')->paginate(25)->withQueryString()
            ->through(fn (StudentCertificate $c) => $this->row($c));

        return Inertia::render('SchoolAdmin/Certificates/Index', [
            'certificates' => $rows,
            'filters' => ['type' => $type, 'search' => $search],
            'types' => collect(StudentCertificate::TYPES)->map(fn ($t, $k) => ['value' => $k, 'label' => $t['title']])->values(),
            'canIssue' => $request->user()->can('students.certificates'),
        ]);
    }

    /** The issue form for one student, filled in from their record */
    public function create(Request $request, Student $student): Response
    {
        $type = $request->type === 'transfer' ? 'transfer' : 'testimonial';
        $student->loadMissing('schoolClass:id,name');

        return Inertia::render('SchoolAdmin/Certificates/Issue', [
            'student' => [
                'id' => $student->id,
                'name' => $student->full_name,
                'admission_no' => $student->admission_no,
                'class' => $student->schoolClass?->name,
                'status' => $student->status,
                'has_photo' => (bool) $student->photo,
            ],
            'type' => $type,
            'title' => StudentCertificate::TYPES[$type]['title'],
            'defaults' => $this->certificates->defaults($student, $type),
            'signers' => $this->certificates->signers($student->school_id),
            'conduct' => StudentCertificate::CONDUCT,
            'previous' => StudentCertificate::where('student_id', $student->id)->latest('id')->get()->map(fn ($c) => $this->row($c)),
        ]);
    }

    public function store(Request $request, Student $student): RedirectResponse
    {
        $data = $request->validate([
            'type' => ['required', Rule::in(array_keys(StudentCertificate::TYPES))],
            'signer_id' => ['nullable', 'integer', Rule::exists('report_card_signers', 'id')->where('school_id', $student->school_id)],
            'mark_left' => 'boolean',
            'details' => 'required|array',
            'details.date_admitted' => 'nullable|date',
            'details.date_left' => 'required|date|after_or_equal:details.date_admitted',
            'details.class_admitted' => 'nullable|string|max:60',
            'details.last_class' => 'required|string|max:60',
            'details.conduct' => ['required', Rule::in(StudentCertificate::CONDUCT)],
            'details.academic_ability' => ['nullable', Rule::in(StudentCertificate::CONDUCT)],
            'details.offices_held' => 'nullable|string|max:300',
            'details.activities' => 'nullable|string|max:300',
            'details.exams' => 'nullable|string|max:300',
            'details.reason_for_leaving' => 'nullable|string|max:200',
            'details.destination_school' => 'nullable|string|max:150',
            'details.fees_cleared' => 'boolean',
            'details.remark' => 'nullable|string|max:600',
            'details.show_photo' => 'boolean',
        ], [
            'details.date_left.after_or_equal' => 'The leaving date must be on or after the admission date.',
            'details.last_class.required' => 'Give the class the student was in when they left.',
        ]);

        $keys = ['date_admitted', 'date_left', 'class_admitted', 'last_class', 'conduct', 'academic_ability', 'offices_held',
            'activities', 'exams', 'reason_for_leaving', 'destination_school', 'fees_cleared', 'remark', 'show_photo'];
        $details = array_intersect_key($data['details'], array_flip($keys));
        $details['fees_cleared'] = (bool) ($details['fees_cleared'] ?? false);
        $details['show_photo'] = (bool) ($details['show_photo'] ?? false);
        if ($data['type'] === 'testimonial') {
            unset($details['reason_for_leaving'], $details['destination_school'], $details['fees_cleared']);
        } else {
            unset($details['academic_ability'], $details['exams']);
        }

        $certificate = $this->certificates->issue($student, $data['type'], $details, $data['signer_id'] ?? null, $request->user(), $request->boolean('mark_left'));

        return redirect()->route('school.students.show', ['student' => $student, 'tab' => 'certificates'])
            ->with('success', "{$certificate->title()} {$certificate->serial} issued. Download it below.");
    }

    public function pdf(StudentCertificate $certificate): HttpResponse
    {
        $data = $this->certificates->pdfData($certificate);
        $bytes = Pdf::loadView('certificates.certificate', $data)->setPaper('a4', 'portrait')->output();

        return response($bytes, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.Str::slug($certificate->title().' '.$certificate->serial.' '.($certificate->details['name'] ?? '')).'.pdf"',
        ]);
    }

    /** A wrong or cancelled certificate: it stays on record, and the public check shows it as revoked */
    public function revoke(Request $request, StudentCertificate $certificate): RedirectResponse
    {
        $data = $request->validate(['reason' => 'required|string|max:255'], ['reason.required' => 'Say why this certificate is being revoked.']);
        abort_if($certificate->isRevoked(), 422, 'This certificate is already revoked.');

        $certificate->update(['revoked_at' => now(), 'revoke_reason' => $data['reason'], 'revoked_by' => $request->user()->id]);

        return back()->with('success', "{$certificate->serial} revoked.");
    }

    /** One line in a list of certificates */
    public static function row(StudentCertificate $c): array
    {
        return [
            'id' => $c->id,
            'type' => $c->type,
            'title' => $c->title(),
            'serial' => $c->serial,
            'issued_on' => $c->issued_on?->toDateString(),
            'student' => $c->details['name'] ?? $c->student?->full_name,
            'student_id' => $c->student_id,
            'admission_no' => $c->details['admission_no'] ?? null,
            'issued_by' => $c->issuer?->name,
            'revoked' => $c->isRevoked(),
            'revoke_reason' => $c->revoke_reason,
            'verify_code' => $c->verify_code,
        ];
    }
}
