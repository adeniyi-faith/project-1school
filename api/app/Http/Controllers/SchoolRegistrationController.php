<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Http\Requests\StoreSchoolRequest;
use App\Models\School;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class SchoolRegistrationController extends Controller
{
    /**
     * Registers a new school and makes the signed-in Supabase user its
     * School Admin. One Supabase login can only ever register once.
     */
    public function store(StoreSchoolRequest $request): JsonResponse
    {
        $supabaseUserId = $request->attributes->get('supabase_user_id');
        $email = $request->attributes->get('supabase_user_email');

        if (User::query()->where('supabase_user_id', $supabaseUserId)->exists()) {
            return response()->json([
                'message' => 'This account is already registered to a school.',
            ], 409);
        }

        $user = DB::transaction(function () use ($request, $supabaseUserId, $email) {
            $school = School::create([
                'code' => School::nextCode(),
                'name' => $request->string('school_name'),
            ]);

            return User::create([
                'school_id' => $school->id,
                'supabase_user_id' => $supabaseUserId,
                'name' => $request->string('admin_name'),
                'email' => $email,
                'password' => Hash::make(Str::random(40)),
                'role' => UserRole::SchoolAdmin,
            ]);
        });

        return response()->json([
            'school' => [
                'code' => $user->school->code,
                'name' => $user->school->name,
            ],
            'user' => [
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
            ],
        ], 201);
    }
}
