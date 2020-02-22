<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Socialite;

class GitHubController extends Controller
{
    public function redirect()
    {
        return Socialite::driver('github')->redirect();
    }

    public function callback()
    {
        $user = Socialite::driver('github')->user();

        dd($user);
    }
}
