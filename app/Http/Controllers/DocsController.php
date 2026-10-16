<?php

namespace App\Http\Controllers;

use App\Support\Docs;

/** The Guides area on the platform's front page: how to use SchoolRuns, and how developers build on it */
class DocsController extends Controller
{
    public function index()
    {
        return view('docs.index', ['groups' => Docs::groups()]);
    }

    public function show(string $slug)
    {
        $page = Docs::find($slug);
        abort_if($page === null, 404);

        return view('docs.page', ['page' => $page, 'groups' => Docs::groups(), 'html' => Docs::render($slug)] + Docs::neighbours($slug));
    }
}
