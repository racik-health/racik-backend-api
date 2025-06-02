<?php

namespace App\Http\Controllers\API\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed|max:255',
        ]);

        try {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => bcrypt($validated['password']),
                'role' => 'user'
            ]);

            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'code' => 201,
                'message' => 'User created successfully',
                'data' => [
                    'access_token' => $token,
                    'token_type' => 'Bearer',
                    'user' => $user->load('profile')
                ]
            ], 201);
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

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        if (!Auth::attempt($credentials)) {
            return response()->json([
                'code' => 401,
                'message' => 'Email atau password salah',
                'data' => null,
            ], 401);
        }

        $user = Auth::user();
        $user->tokens()->delete();
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'code' => 200,
            'message' => 'User logged in successfully',
            'data' => [
                'access_token' => $token,
                'token_type' => 'Bearer',
                'user' => $user->load('profile')
            ]
        ], 200);
    }

    public function logout(Request $request)
    {
        try {
            $request->user()->currentAccessToken()->delete();

            return response()->json([
                'code' => 200,
                'message' => 'User logged out successfully',
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

    public function user(Request $request)
    {
        return response()->json([
            'code' => 200,
            'message' => 'User fetched successfully',
            'data' => $request->user()->load('profile'),
        ], 200);
    }
}
