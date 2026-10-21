<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Support\EnvironmentView;
use Illuminate\Http\Request;
use Inertia\Inertia;

/** Read-only: the app's important settings with secrets hidden, so nobody has to open cPanel to check a value */
class EnvironmentController extends Controller
{
    public function index(Request $request)
    {
        $data = ['groups' => EnvironmentView::groups()];

        if ($request->expectsJson() && ! $request->header('X-Inertia')) {
            return response()->json($data);
        }

        return Inertia::render('SuperAdmin/Environment/Index', $data);
    }
}
