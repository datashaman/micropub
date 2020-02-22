<?php

namespace App\Http\Controllers;

use GrahamCampbell\GitHub\Facades\GitHubFactory;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index()
    {
        $repositories = [];

        if (auth()->check()) {
            $connection = GitHubFactory::make(
                [
                    'method' => 'token',
                    'token' => auth()->user()->token,
                ]
            );

            dd($connection->me()->auth());
        }

        return view('home');
    }
}
