<?php

namespace App\Http\Controllers\API\Patient;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class PatientUserProfileController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $user = auth()->user();

        // if ($user->id !== $id) {
        //     return response()->json([
        //         'code' => 403,
        //         'message' => 'Forbidden',
        //         'data' => null,
        //     ], 403);
        // }

        return response()->json([
            'code' => 200,
            'message' => 'User profile fetched successfully',
            'data' => $user->load('profile'),
        ], 200);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $user = auth()->user();

        $validatedProfileData = $request->validate([
            'date_of_birth' => 'nullable|date_format:Y-m-d',
            'allergies' => 'nullable|string|max:1000',
            'medical_conditions' => 'nullable|string|max:1000',
        ]);

        $validatedUserData = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        try {
            if ($user->profile) {
                $user->profile()->update($validatedProfileData);
            } else {
                $user->profile()->create($validatedProfileData);
            }

            if ($request->has('name') && $user->name !== $validatedUserData['name']) {
                $user->update($validatedUserData);
            }

            return response()->json([
                'code' => 200,
                'message' => 'User profile updated successfully',
                'data' => $user->load('profile'),
            ], 200);
        } catch (\Exception $e) {
            Log::error($e->getMessage());

            return response()->json([
                'code' => 500,
                'message' => 'Something went wrong',
                'data' => null,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    public function updatePassword(Request $request)
    {
        $user = auth()->user();

        $validated = $request->validate([
            'current_password' => [
                'required',
                function ($attribute, $value, $fail) use ($user) {
                    if (!Hash::check($value, $user->password)) {
                        $fail('Password lama Anda salah.');
                    }
                }
            ],
            'password' => 'required|string|min:8|max:255',
        ]);

        try {
            $user->update([
                'password' => bcrypt($validated['password']),
            ]);

            return response()->json([
                'code' => 200,
                'message' => 'Password updated successfully',
                'data' => null,
            ], 200);
        } catch (\Exception $e) {
            Log::error($e->getMessage());

            return response()->json([
                'code' => 500,
                'message' => 'Something went wrong',
                'data' => null,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
