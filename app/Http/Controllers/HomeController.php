<?php

namespace App\Http\Controllers;

use GrahamCampbell\GitHub\Facades\GitHub;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index()
    {
        $repositories = [];

        if (auth()->check()) {
            $connection = GitHub::createConnection(
                [
                    'method' => 'token',
                    'token' => $user->token,
                ]
            );

            dd($connection->me()->auth());
        }

        return view('home');
    }
}
