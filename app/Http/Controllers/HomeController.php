<?php

namespace App\Http\Controllers;

use GrahamCampbell\GitHub\GitHubFactory;
use Illuminate\Http\Request;
use Socialite;

class HomeController extends Controller
{
    public function index()
    {
        $repositories = [];

        if (auth()->check()) {
            $user = auth()->user();
            $connection = resolve(GitHubFactory::class)->make(
                [
                    'method' => 'token',
                    'token' => decrypt($user->token),
                ]
            );
            $repositories = $connection->me()->repositories();
        }

        return view(
            'home',
            [
                'repositories' => $repositories,
            ]
        );
    }
}
