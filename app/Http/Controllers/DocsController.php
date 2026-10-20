<?php

namespace App\Http\Controllers;

use App\Support\Docs;

/** The Guides area on the platform's front page: how to use Adino School Systems, and how developers build on it */
class DocsController extends Controller
{
    public function __construct()
    {
        // Small icons the layout and both pages draw
        view()->share('docIcon', '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8z"/><path d="M14 3v5h5M9 13h6M9 17h6"/></svg>');
        view()->share('searchIcon', '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>');
    }

    public function index()
    {
        return view('docs.index', ['groups' => Docs::groups()]);
    }

    public function show(string $slug)
    {
        $page = Docs::find($slug);
        abort_if($page === null, 404);

        $groups = Docs::groups();
        $related = array_values(array_filter($groups[$page['group']] ?? [], fn ($p) => $p['slug'] !== $slug));

        return view('docs.page', ['page' => $page, 'groups' => $groups, 'related' => $related, 'html' => Docs::render($slug)] + Docs::neighbours($slug));
    }
}
