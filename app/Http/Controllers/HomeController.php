<?php

namespace App\Http\Controllers;

use GrahamCampbell\GitHub\GitHubFactory;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index()
    {
        $repositories = [];

        if (auth()->check()) {
            $user = auth()->user();
            $socialUser = Socialite::driver($user->provider)->userFromToken(decrypt($user->token));
            dd($socialUser);
        }

        return view('home');
    }
}
