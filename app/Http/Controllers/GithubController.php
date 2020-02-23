<?php

namespace App\Http\Controllers;

use App\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Socialite;

class GithubController extends Controller
{
    public function login()
    {
        return Socialite::driver('github')
            ->stateless()
            ->scopes(['repo'])
            ->redirect();
    }

    public function logout()
    {
        Auth::logout();

        return redirect()->route('home');
    }

    public function callback()
    {
        $socialUser = Socialite::driver('github')->stateless()->user();

        Log::debug('Social User', [
            'refreshToken' => $socialUser->refreshToken,
            'expiresIn' => $socialUser->expiresIn,
        ]);

        $user = User::updateOrCreate(
            [
                'provider' => 'github',
                'provider_id' => $socialUser->getId(),
            ],
            [
                'avatar' => $socialUser->getAvatar(),
                'email' => $socialUser->getEMail(),
                'name' => $socialUser->getName(),
                'nickname' => $socialUser->getNickname(),
                'token' => encrypt($socialUser->token),
            ]
        );

        Auth::login($user);

        return redirect()->route('home');
    }
}
