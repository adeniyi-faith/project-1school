<?php

namespace App\Http\Controllers\SchoolAdmin\Website;

use App\Forms\FormSchema;
use App\Forms\FormService;
use App\Forms\FormTemplates;
use App\Models\SiteForm;
use App\Models\SiteFormSubmission;
use App\Support\SimpleXlsx;
use App\Website\Extensions;
use App\Website\SiteRenderer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/** Website > Extensions > Forms: build forms, see the answers, download them */
class WebsiteFormsController extends WebsiteBase
{
    public function __construct(private FormService $forms) {}

    private function guardOn(): void
    {
        abort_unless(Extensions::enabled($this->getSchoolId(), 'forms'), 404);
    }

    public function index(Request $request): Response
    {
        $this->guardOn();
        $school = $this->school();
        $new = SiteFormSubmission::where('status', 'new')->selectRaw('form_id, count(*) as n')->groupBy('form_id')->pluck('n', 'form_id');

        return Inertia::render('SchoolAdmin/Website/Forms', $this->shell($school, $this->site($school)) + [
            'forms' => SiteForm::withCount('submissions')->latest('id')->get()->map(fn ($f) => [
                'id' => $f->id, 'name' => $f->name, 'active' => $f->active, 'fields' => count($f->fields ?? []), 'answers' => $f->submissions_count, 'new' => (int) ($new[$f->id] ?? 0),
                'url' => SiteRenderer::homeUrl($school).'/form/'.$f->id,
            ])->values(),
            'templates' => FormTemplates::LIST,
            'canManage' => $request->user()->can('forms.manage'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->guardOn();
        $data = $request->validate(['template' => ['required', Rule::in(array_keys(FormTemplates::LIST))]]);
        $form = new SiteForm(FormTemplates::make($data['template'], $this->school()) + ['active' => true]);
        $form->school_id = $this->getSchoolId();
        $form->save();

        return redirect('/school/website/forms/'.$form->id)->with('success', 'Form made. Change any question, then press Save.');
    }

    public function edit(Request $request, int $form): Response
    {
        $this->guardOn();
        $school = $this->school();
        $row = SiteForm::findOrFail($form);

        return Inertia::render('SchoolAdmin/Website/FormEdit', $this->shell($school, $this->site($school)) + [
            'form' => [
                'id' => $row->id, 'name' => $row->name, 'intro' => (string) $row->intro, 'fields' => $row->fields ?? [], 'consents' => $row->consents ?? [],
                'button_text' => $row->button_text, 'success_message' => (string) $row->success_message, 'notify_email' => (string) $row->notify_email,
                'newsletter_optin' => $row->newsletter_optin, 'active' => $row->active,
            ],
            'types' => FormSchema::TYPES, 'choiceTypes' => FormSchema::CHOICES,
            'limits' => ['fields' => FormSchema::MAX_FIELDS, 'options' => FormSchema::MAX_OPTIONS, 'consents' => FormSchema::MAX_CONSENTS],
            'presets' => FormTemplates::consentPresets($school),
            'shareUrl' => SiteRenderer::homeUrl($school).'/form/'.$row->id,
            'answers' => $row->submissions()->count(),
            'newsletterReady' => Extensions::enabled($school->id, 'newsletter') && \App\Newsletter\NewsletterSettings::resolve($school->id) !== null,
            'canManage' => $request->user()->can('forms.manage'),
        ]);
    }

    public function update(Request $request, int $form): RedirectResponse
    {
        $this->guardOn();
        $row = SiteForm::findOrFail($form);
        $data = $request->validate([
            'name' => 'required|string|max:120', 'intro' => 'nullable|string|max:400', 'button_text' => 'nullable|string|max:40',
            'success_message' => 'nullable|string|max:300', 'notify_email' => 'nullable|email|max:150',
            'newsletter_optin' => 'boolean', 'active' => 'boolean', 'fields' => 'array', 'consents' => 'nullable|array',
        ]);
        $fields = FormSchema::cleanFields($data['fields'] ?? []);
        if ($problem = FormSchema::problem($fields)) {
            return back()->withErrors(['fields' => $problem]);
        }
        if (! empty($data['newsletter_optin']) && ! collect($fields)->contains('type', 'email')) {
            return back()->withErrors(['newsletter_optin' => 'To offer the newsletter, add an "Email address" question first.']);
        }

        $row->update([
            'name' => trim($data['name']), 'intro' => trim((string) ($data['intro'] ?? '')) ?: null, 'button_text' => trim((string) ($data['button_text'] ?? '')) ?: 'Send',
            'success_message' => trim((string) ($data['success_message'] ?? '')) ?: null, 'notify_email' => trim((string) ($data['notify_email'] ?? '')) ?: null,
            'newsletter_optin' => (bool) ($data['newsletter_optin'] ?? false), 'active' => (bool) ($data['active'] ?? true),
            'fields' => $fields, 'consents' => FormSchema::cleanConsents($data['consents'] ?? []),
        ]);

        return back()->with('success', 'Form saved.');
    }

    /** Removes the form and every answer sent to it */
    public function destroy(int $form): RedirectResponse
    {
        $this->guardOn();
        SiteForm::findOrFail($form)->delete();

        return redirect('/school/website/forms')->with('success', 'Form and its answers deleted.');
    }

    public function answers(Request $request, int $form): Response
    {
        $this->guardOn();
        $school = $this->school();
        $row = SiteForm::findOrFail($form);
        $status = in_array($request->query('status'), ['new', 'read'], true) ? $request->query('status') : null;

        $list = $row->submissions()->when($status, fn ($q) => $q->where('status', $status))->latest('id')->paginate(25)->withQueryString()
            ->through(fn ($s) => ['id' => $s->id, 'status' => $s->status, 'at' => $s->created_at->toIso8601String(), 'answers' => $s->answers, 'consents' => $s->consents ?? [], 'newsletter' => $s->newsletter_asked, 'page' => $s->page]);

        return Inertia::render('SchoolAdmin/Website/FormAnswers', $this->shell($school, $this->site($school)) + [
            'form' => ['id' => $row->id, 'name' => $row->name],
            'submissions' => $list,
            'counts' => ['all' => $row->submissions()->count(), 'new' => $row->submissions()->where('status', 'new')->count()],
            'filters' => ['status' => (string) $status],
            'canManage' => $request->user()->can('forms.manage'),
        ]);
    }

    public function markRead(Request $request, int $form, int $submission): RedirectResponse
    {
        $this->guardOn();
        SiteFormSubmission::where('form_id', $form)->findOrFail($submission)->update(['status' => $request->boolean('read', true) ? 'read' : 'new']);

        return back();
    }

    public function markAllRead(int $form): RedirectResponse
    {
        $this->guardOn();
        SiteForm::findOrFail($form)->submissions()->where('status', 'new')->update(['status' => 'read']);

        return back()->with('success', 'All marked as read.');
    }

    /** Takes one answer away for good (for someone who asks to be forgotten) */
    public function removeAnswer(int $form, int $submission): RedirectResponse
    {
        $this->guardOn();
        SiteFormSubmission::where('form_id', $form)->findOrFail($submission)->delete();

        return back()->with('success', 'Answer deleted.');
    }

    public function export(int $form): \Symfony\Component\HttpFoundation\Response
    {
        $this->guardOn();
        $row = SiteForm::findOrFail($form);
        $subs = $row->submissions()->orderBy('id')->get();
        $labels = $subs->flatMap(fn ($s) => collect($s->answers)->pluck('label'))->unique()->values()->all();

        $rows = [array_merge(['Sent on', 'Page'], $labels, ['Privacy ticks', 'Asked for the newsletter'])];
        foreach ($subs as $s) {
            $by = collect($s->answers)->pluck('value', 'label');
            $ticks = collect($s->consents ?? [])->map(fn ($c) => ($c['agreed'] ? 'Agreed: ' : 'Not agreed: ').$c['text'])->implode(' | ');
            $rows[] = array_merge([$s->created_at->format('Y-m-d H:i'), (string) $s->page], collect($labels)->map(fn ($l) => (string) ($by[$l] ?? ''))->all(), [$ticks, $s->newsletter_asked ? 'Yes' : 'No']);
        }
        $name = \Illuminate\Support\Str::slug($row->name) ?: 'form';
        $xlsx = (new SimpleXlsx)->sheet(\Illuminate\Support\Str::limit($row->name, 28, ''), $rows);

        return response($xlsx->bytes(), 200, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'Content-Disposition' => 'attachment; filename="'.$name.'-answers.xlsx"']);
    }
}
