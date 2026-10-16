<?php

namespace App\Http\Controllers;

use App\Forms\FormSchema;
use App\Forms\FormService;
use App\Models\School;
use App\Models\SchoolSite;
use App\Models\SchoolSitePage;
use App\Models\SiteForm;
use App\Website\Extensions;
use App\Website\SiteRenderer;
use App\Website\Visitor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/** The forms a school built, as visitors meet them: shown on a page or on their own address, and sent from there */
class SiteFormPublicController extends Controller
{
    public function __construct(private FormService $forms) {}

    /** A form on its own address, handy to share in an email or a message */
    public function show(string $slug, int $form)
    {
        [$school, $site] = $this->live($slug);
        $row = $this->form($school, $form);
        $pages = SchoolSitePage::withoutGlobalScopes()->where('school_id', $school->id)->where('is_published', true)->whereNull('deleted_at')->orderBy('position')->orderBy('id')->get();

        return SiteRenderer::renderPlain($school, $site, $pages, 'website.form', 'form/'.$row->id, $row->name, $row->intro ?: 'Fill in the form below.', [
            'siteForm' => $row, 'offerNewsletter' => $this->forms->newsletterOffered($school, $row),
        ]);
    }

    public function submit(Request $request, string $slug, int $form)
    {
        [$school] = $this->live($slug);
        $row = $this->form($school, $form);
        $bag = 'form'.$row->id;
        $fields = $row->fields ?? [];
        $consents = $row->consents ?? [];

        // questions that only appear after another answer are checked only when that answer was given
        $visible = FormSchema::visible($fields, (array) $request->input('a', []));
        $validator = Validator::make($request->all(), FormSchema::rules($fields, $consents, $visible), [
            'accepted' => 'Please tick :attribute.', 'required' => 'Please fill in :attribute.', 'mimes' => ':attribute must be a PDF, Word document, JPG or PNG.',
            'max.file' => ':attribute must be smaller than '.(FormSchema::FILE_KB / 1024).' MB.', 'uploaded' => ':attribute could not be uploaded. It may be too big (the limit is '.(FormSchema::FILE_KB / 1024).' MB).',
        ], FormSchema::attributeNames($fields, $consents));
        if ($validator->fails()) {
            return back()->withInput()->withErrors($validator, $bag);
        }

        // a form that takes files gets a tighter limit, because every post can store a file
        $verdict = FormService::hasFiles($row) ? Visitor::inspect($request, $school->id, 'form-'.$row->id, 5, 100) : Visitor::inspect($request, $school->id, 'form-'.$row->id, 10, 500);
        if ($verdict === Visitor::ROBOT) {
            return $this->thanks($row, $request); // thanked and ignored
        }
        if ($verdict !== Visitor::OK) {
            return back()->withInput()->withErrors(['form' => Visitor::message($verdict)], $bag);
        }

        $page = parse_url((string) $request->headers->get('referer'), PHP_URL_PATH) ?: null;
        $this->forms->store($school, $row, $validator->validated() + ['join_newsletter' => $request->boolean('join_newsletter')], (string) $request->ip(), $page);

        return $this->thanks($row, $request);
    }

    private function thanks(SiteForm $form, Request $request)
    {
        return redirect(url()->previous().'#form-'.$form->id)->with('form_done', [$form->id => $form->success_message ?: 'Thank you. We have received your answers.']);
    }

    private function live(string $slug): array
    {
        $school = School::where('slug', $slug)->where('status', 'active')->firstOrFail();
        $site = SchoolSite::withoutGlobalScopes()->where('school_id', $school->id)->where('enabled', true)->firstOrFail();
        abort_unless(Extensions::enabled($school->id, 'forms'), 404);

        return [$school, $site];
    }

    /** A form of this school only, and only while the school keeps it open */
    private function form(School $school, int $id): SiteForm
    {
        return SiteForm::withoutGlobalScopes()->where('school_id', $school->id)->where('active', true)->findOrFail($id);
    }
}
