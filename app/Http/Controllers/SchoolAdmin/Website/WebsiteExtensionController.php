<?php

namespace App\Http\Controllers\SchoolAdmin\Website;

use App\Website\Extensions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/** Website > Extensions: one card per extra feature, each with its own on/off switch for this school */
class WebsiteExtensionController extends WebsiteBase
{
    public function index(): Response
    {
        $school = $this->school();
        $site = $this->site($school);
        $on = Extensions::switchedOn($school->id);

        return Inertia::render('SchoolAdmin/Website/Extensions', $this->shell($school, $site) + [
            'extensions' => collect(Extensions::all())->map(fn ($e, $key) => [
                'key' => $key, 'name' => $e['name'], 'description' => $e['description'], 'needs' => $e['needs'], 'ready' => $e['ready'],
                'hidden' => Extensions::hiddenByPlan($school->id, $key),
                'on' => $e['ready'] && in_array($key, $on, true),
                'setup' => $e['ready'] ? route('school.website.extensions.setup', $key, false) : null,
            ])->values(),
        ]);
    }

    public function toggle(Request $request, string $key): RedirectResponse
    {
        $ext = Extensions::find($key);
        abort_if(! $ext, 404);
        $data = $request->validate(['on' => 'required|boolean']);
        $schoolId = $this->getSchoolId();

        if (! $ext['ready']) {
            return back()->withErrors(['extension' => $ext['name'].' is not ready yet.']);
        }
        if ($data['on'] && Extensions::hiddenByPlan($schoolId, $key)) {
            return back()->withErrors(['extension' => $ext['name'].' is not part of your school\'s plan.']);
        }
        Extensions::set($schoolId, $key, (bool) $data['on']);

        return back()->with('success', $ext['name'].($data['on'] ? ' is on.' : ' is off. Anything already on your pages stays saved, but visitors will not see it.'));
    }

    /** Where an extension's own screens begin (so the card's "Open" button knows where to go) */
    public function setup(string $key): RedirectResponse
    {
        $ext = Extensions::find($key);
        abort_if(! $ext || ! $ext['ready'], 404);

        return redirect($key === 'newsletter' ? '/school/website/newsletter' : '/school/website/extensions');
    }
}
