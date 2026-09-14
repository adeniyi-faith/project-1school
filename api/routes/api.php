<?php

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
