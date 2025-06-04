<?php

namespace App\Http\Controllers\API\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Socialite\Facades\Socialite;

class SocialiteController extends Controller
{
    public string $FRONTEND_BASE_URL;

    public function __construct()
    {
        $this->FRONTEND_BASE_URL = env('FRONTEND_BASE_URL', null);
    }

    public function redirectToGoogle()
    {
        try {
            return Socialite::driver('google')
                ->stateless()
                ->redirect();
        } catch (\Exception $e) {
            Log::error('Google redirect error: ' . $e->getMessage());

            return redirect("{$this->FRONTEND_BASE_URL}/login?error=google_redirect_failed&message=" . urlencode($e->getMessage()));
        }
    }

    public function handleGoogleCallback()
    {
        try {
            $googleUser = Socialite::driver('google')
                ->stateless()
                ->user();

            if (!$googleUser) {
                Log::error('Google callback error: User not found');

                return redirect("{$this->FRONTEND_BASE_URL}/login?error=google_user_not_found");
            }

            $user = User::firstWhere('email', '=', $googleUser->getEmail());

            if ($user) {
                if (!$user->google_id) {
                    $user->google_id = $googleUser->getId();
                    $user->save();
                }
            } else {
                $user = User::create([
                    'name' => $googleUser->getName(),
                    'email' => $googleUser->getEmail(),
                    // 'phone' => null,
                    'password' => bcrypt(Str::random(32)),
                    'role' => 'user',
                    'google_id' => $googleUser->getId(),
                    'email_verified_at' => now(),
                ]);
            }

            $user->tokens()->delete();
            $token = $user->createToken('auth_token')->plainTextToken;

            $queryParam = http_build_query([
                'access_token' => $token,
                // 'user' => $user->load('profile'),
            ]);

            return redirect("{$this->FRONTEND_BASE_URL}/auth/google/callback?{$queryParam}");
        } catch (ValidationException $e) {
            Log::error('Google callback validation error: ' . $e->getMessage());

            return redirect("{$this->FRONTEND_BASE_URL}/login?error=validation_failed&message=" . urlencode($e->getMessage()));
        } catch (\Exception $e) {
            Log::error('Google callback error: ' . $e->getMessage());

            return redirect("{$this->FRONTEND_BASE_URL}/login?error=google_callback_failed&message=" . urlencode($e->getMessage()));
        }
    }
}
