<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Services\SchoolExportService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/** Download a copy of one school's data. Needs a written reason and is logged. Reads only. */
class SchoolExportController extends Controller
{
    public function download(Request $request, School $school, SchoolExportService $export): BinaryFileResponse
    {
        $data = $request->validate(['reason' => 'required|string|min:10|max:500']);

        $path = $export->build($school, $request->user(), trim($data['reason']));
        $name = 'school-'.$school->slug.'-'.now()->format('Y-m-d').'.zip';

        return response()->download($path, $name)->deleteFileAfterSend(true);
    }
}
