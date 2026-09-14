<?php

use App\Http\Controllers\SchoolRegistrationController;
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

// Proves the frontend's Supabase login and Laravel's token check are
// wired together correctly: it echoes back who Supabase says is asking.
Route::get('/me', function (Request $request) {
    return response()->json([
        'supabase_user_id' => $request->attributes->get('supabase_user_id'),
        'supabase_user_email' => $request->attributes->get('supabase_user_email'),
    ]);
})->middleware('supabase.auth');

// A school signs up here: the frontend must already have a Supabase
// login for the person registering, since that's who becomes the
// School Admin.
Route::post('/schools', [SchoolRegistrationController::class, 'store'])
    ->middleware('supabase.auth');
