<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Package;
use App\Website\Extensions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/** Platform owner: which website extensions each plan can use */
class ExtensionController extends Controller
{
    public function index(): Response
    {
        $hidden = Extensions::hiddenMap();

        return Inertia::render('SuperAdmin/Extensions/Index', [
            'plans' => Package::orderBy('price_monthly')->get(['id', 'name']),
            'extensions' => collect(Extensions::all())->map(fn ($e, $key) => [
                'key' => $key, 'name' => $e['name'], 'ready' => $e['ready'], 'hidden_from' => $hidden[$key] ?? [],
            ])->values(),
        ]);
    }

    public function save(Request $request): RedirectResponse
    {
        $data = $request->validate(['hidden' => 'present|array', 'hidden.*' => 'array', 'hidden.*.*' => 'integer|exists:packages,id']);
        Extensions::saveHiddenMap($data['hidden']);

        return back()->with('success', 'Saved. Schools on a plan that hides an extension can no longer switch it on, and it stops showing on their sites.');
    }
}
