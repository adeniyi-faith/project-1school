<?php

use App\Http\Controllers\SchoolRegistrationController;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// A simple endpoint the frontend can call to prove the API is reachable.
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'service' => 'schoolruns-api',
    ]);
});

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Tells the frontend who's asking and, if they've already registered
// a school, which one — so a returning visit (or a page refresh) can
// show the right screen instead of asking them to register again.
Route::get('/me', function (Request $request) {
    $user = User::query()
        ->where('supabase_user_id', $request->attributes->get('supabase_user_id'))
        ->with('school')
        ->first();

    return response()->json([
        'supabase_user_id' => $request->attributes->get('supabase_user_id'),
        'supabase_user_email' => $request->attributes->get('supabase_user_email'),
        'role' => $user?->role,
        'school' => $user?->school ? [
            'code' => $user->school->code,
            'name' => $user->school->name,
        ] : null,
    ]);
})->middleware('supabase.auth');

// A school signs up here: the frontend must already have a Supabase
// login for the person registering, since that's who becomes the
// School Admin.
Route::post('/schools', [SchoolRegistrationController::class, 'store'])
    ->middleware('supabase.auth');
