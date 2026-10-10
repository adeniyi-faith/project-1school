<?php

namespace App\Http\Controllers\SchoolAdmin;

use App\Http\Controllers\Controller;
use App\Models\ReportCardDesign;
use App\Models\ReportCardSigner;
use App\Models\SchoolClass;
use App\Services\ReportCardService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

/**
 * Report card designs: layout, colours, titles, which parts show, who comments and signs
 * (with their own titles and signature images), the school stamp, and which classes use it.
 */
class ReportCardDesignController extends Controller
{
    /** Signatures and stamps: PNG (best with a see-through background) or JPG, up to 1 MB */
    private const IMAGE_RULE = 'nullable|image|mimes:png,jpg,jpeg|max:1024';

    public function index(Request $request): Response
    {
        $designs = ReportCardDesign::with('signers')->orderByDesc('is_default')->orderBy('name')->get();
        if ($designs->isEmpty()) {
            ReportCardDesign::forClass($request->user()->school_id ?? 0, null);
            $designs = ReportCardDesign::with('signers')->get();
        }

        return Inertia::render('SchoolAdmin/Academics/ReportCardDesigns', [
            'designs' => $designs->map(fn (ReportCardDesign $d) => [
                'id' => $d->id,
                'name' => $d->name,
                'is_default' => $d->is_default,
                'template' => $d->template,
                'primary_color' => $d->primary_color,
                'accent_color' => $d->accent_color,
                'font_size' => $d->font_size,
                'paper' => $d->paper,
                'term_title' => $d->term_title,
                'session_title' => $d->session_title,
                'footer_note' => $d->footer_note,
                'options' => $d->settings(),
                'has_stamp' => (bool) $d->stamp_path,
                'signers' => $d->signers->map(fn (ReportCardSigner $s) => [
                    'id' => $s->id, 'label' => $s->label, 'name' => $s->name, 'has_comment' => $s->has_comment,
                    'writer_permission' => $s->writer_permission, 'has_signature' => (bool) $s->signature_path,
                ]),
            ]),
            'classes' => SchoolClass::orderBy('numeric_name')->orderBy('id')->get(['id', 'name', 'report_card_design_id']),
            'templates' => ReportCardDesign::TEMPLATES,
            'writers' => collect(ReportCardSigner::WRITERS)->map(fn ($label, $value) => ['value' => $value, 'label' => $label])->values(),
            'optionLabels' => self::OPTION_LABELS,
            'canEdit' => $request->user()->can('settings.edit'),
        ]);
    }

    /** A new design, copied from an existing one (with its signers) so the school starts from something that works */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:80',
            'copy_from' => 'nullable|integer',
        ]);

        $source = isset($data['copy_from']) ? ReportCardDesign::with('signers')->findOrFail($data['copy_from']) : null;
        $source ??= ReportCardDesign::forClass($request->user()->school_id ?? 0, null)->load('signers');

        $design = DB::transaction(function () use ($source, $data) {
            $copy = $source->replicate(['is_default', 'stamp_path']);
            $copy->fill(['name' => $data['name'], 'is_default' => false])->save();
            foreach ($source->signers as $signer) {
                $copy->signers()->create($signer->only('label', 'name', 'has_comment', 'writer_permission', 'sort_order') + ['school_id' => $copy->school_id]);
            }

            return $copy;
        });

        return redirect()->route('school.report-card-designs.index', ['design' => $design->id])->with('success', "Design \"{$design->name}\" created. Change it below, then give it to classes.");
    }

    public function update(Request $request, ReportCardDesign $design): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:80',
            'is_default' => 'boolean',
            'template' => ['required', Rule::in(ReportCardDesign::TEMPLATES)],
            'primary_color' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'accent_color' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'font_size' => ['required', Rule::in(ReportCardDesign::FONT_SIZES)],
            'paper' => ['required', Rule::in(ReportCardDesign::PAPERS)],
            'term_title' => 'required|string|max:80',
            'session_title' => 'required|string|max:80',
            'footer_note' => 'nullable|string|max:300',
            'options' => 'array',
            'options.*' => 'boolean',
            'stamp' => self::IMAGE_RULE,
            'remove_stamp' => 'boolean',
            'signers' => 'present|array|max:6',
            'signers.*.id' => 'nullable|integer',
            'signers.*.label' => 'required|string|max:60',
            'signers.*.name' => 'nullable|string|max:100',
            'signers.*.has_comment' => 'boolean',
            'signers.*.writer_permission' => ['required', Rule::in(array_keys(ReportCardSigner::WRITERS))],
            'signers.*.signature' => self::IMAGE_RULE,
            'signers.*.remove_signature' => 'boolean',
        ], [
            'primary_color.regex' => 'Choose a colour.',
            'accent_color.regex' => 'Choose a colour.',
            'signers.*.label.required' => 'Give each signer a title, for example Class Teacher or Head Teacher.',
        ]);

        $existing = $design->signers()->get()->keyBy('id');
        foreach ($data['signers'] as $i => $row) {
            if (! empty($row['id']) && ! $existing->has($row['id'])) {
                throw ValidationException::withMessages(["signers.$i.label" => 'This signer belongs to another design. Reload the page.']);
            }
        }

        DB::transaction(function () use ($request, $design, $data, $existing) {
            $options = array_intersect_key(($data['options'] ?? []) + $design->settings(), ReportCardDesign::DEFAULT_OPTIONS);
            $design->fill(collect($data)->only('name', 'template', 'primary_color', 'accent_color', 'font_size', 'paper', 'term_title', 'session_title', 'footer_note')->all());
            $design->options = array_map('boolval', $options);

            if ($request->hasFile('stamp')) {
                $design->stamp_path = $this->replaceImage($design->stamp_path, $request->file('stamp'), $design->school_id);
            } elseif ($request->boolean('remove_stamp') && $design->stamp_path) {
                Storage::disk('private')->delete($design->stamp_path);
                $design->stamp_path = null;
            }
            $design->save();

            if ($request->boolean('is_default') && ! $design->is_default) {
                ReportCardDesign::where('school_id', $design->school_id)->update(['is_default' => false]);
                $design->forceFill(['is_default' => true])->save();
            }

            // Signers: update the ones kept, add new ones, remove the rest (their comments go with them)
            $kept = [];
            foreach ($data['signers'] as $i => $row) {
                $signer = ! empty($row['id']) ? $existing[$row['id']] : new ReportCardSigner(['school_id' => $design->school_id, 'report_card_design_id' => $design->id]);
                $signer->fill([
                    'label' => $row['label'],
                    'name' => $row['name'] ?? null,
                    'has_comment' => (bool) ($row['has_comment'] ?? false),
                    'writer_permission' => $row['writer_permission'],
                    'sort_order' => $i,
                ]);
                if ($file = $request->file("signers.$i.signature")) {
                    $signer->signature_path = $this->replaceImage($signer->signature_path, $file, $design->school_id);
                } elseif (! empty($row['remove_signature']) && $signer->signature_path) {
                    Storage::disk('private')->delete($signer->signature_path);
                    $signer->signature_path = null;
                }
                $signer->save();
                $kept[] = $signer->id;
            }
            foreach ($existing->except($kept) as $gone) {
                if ($gone->signature_path) {
                    Storage::disk('private')->delete($gone->signature_path);
                }
                $gone->delete();
            }
        });

        return back()->with('success', 'Design saved. Use "Preview" to see how it prints.');
    }

    public function destroy(ReportCardDesign $design): RedirectResponse
    {
        if ($design->is_default) {
            return back()->with('error', 'This is the default design. Make another design the default first.');
        }

        DB::transaction(function () use ($design) {
            SchoolClass::where('report_card_design_id', $design->id)->update(['report_card_design_id' => null]);
            $design->delete();
        });

        return redirect()->route('school.report-card-designs.index')->with('success', 'Design removed. Its classes now use the default design.');
    }

    /** Choose which classes print with this design. Classes taken off it go back to the default. */
    public function assignClasses(Request $request, ReportCardDesign $design): RedirectResponse
    {
        $data = $request->validate([
            'class_ids' => 'present|array',
            'class_ids.*' => ['integer', Rule::exists('classes', 'id')->where('school_id', $design->school_id)->whereNull('deleted_at')],
        ]);

        DB::transaction(function () use ($design, $data) {
            SchoolClass::where('report_card_design_id', $design->id)->whereNotIn('id', $data['class_ids'])->update(['report_card_design_id' => null]);
            SchoolClass::whereIn('id', $data['class_ids'])->update(['report_card_design_id' => $design->id]);
        });

        return back()->with('success', 'Classes updated.');
    }

    /** A made-up student's card printed with this design: ?type=session for the full-year card */
    public function preview(Request $request, ReportCardDesign $design, ReportCardService $cards): HttpResponse
    {
        $type = $request->input('type') === 'session' ? 'session' : 'term';

        return ReportCardController::pdfResponse("report-cards.{$type}", $cards->sampleCards($design, $type), "Preview {$design->name}");
    }

    private function replaceImage(?string $old, $file, int $schoolId): string
    {
        $path = $file->store("schools/{$schoolId}/report-cards", 'private');
        if ($old) {
            Storage::disk('private')->delete($old);
        }

        return $path;
    }

    /** What each on/off switch is called on the design page */
    public const OPTION_LABELS = [
        'header' => [
            'show_logo' => 'School logo',
            'logo_watermark' => 'Faint logo behind the page',
            'show_motto' => 'Motto',
            'show_address' => 'Address, phone and email',
            'show_photo' => 'Student photo',
            'show_age' => 'Student age',
        ],
        'results' => [
            'show_parts' => 'Score parts (CA1, CA2, Exam ...)',
            'show_subject_position' => 'Position in each subject',
            'show_class_stats' => 'Class average, highest and lowest per subject',
            'show_remarks' => 'Remark per subject (Excellent, Good ...)',
            'show_overall_position' => 'Position in class',
            'show_class_average' => 'Class average',
        ],
        'extras' => [
            'show_behaviour' => 'Behaviour ratings',
            'show_skills' => 'Skills ratings',
            'show_attendance' => 'Days present',
            'show_next_term' => 'Next term begins',
            'show_fees_owed' => 'Fees still owed',
            'show_grade_key' => 'Grade key',
            'show_signatures' => 'Signature lines',
        ],
    ];
}
