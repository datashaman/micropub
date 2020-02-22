<?php

namespace App\Http\Controllers;

use App\User;
use Illuminate\Support\Facades\Auth;
use Socialite;

class GithubController extends Controller
{
    public function redirect()
    {
        return Socialite::driver('github')->redirect();
    }

    public function callback()
    {
        $socialUser = Socialite::driver('github')->user();

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
