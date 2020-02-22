<?php

namespace App\Http\Controllers;

use App\User;
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
                'id' => $socialUser->getId(),
            ],
            [
                'avatar' => $socialUser->getAvatar(),
                'email' => $socialUser->getEMail(),
                'name' => $socialUser->getName(),
                'nickname' => $socialUser->getNickname(),
                'token' => $user->token,
            ]
        );
    }
}
