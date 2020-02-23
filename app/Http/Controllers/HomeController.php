<?php

namespace App\Http\Controllers;

use GrahamCampbell\GitHub\GitHubFactory;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Socialite;

class HomeController extends Controller
{
    public function index()
    {
        $me = session('user.me');

        $client = new Client();
        $response = $client->get($me);

        dd(
            [
                'headers' => $response->getHeaders(),
                'body' => $response->getBody(),
            ]
        );

        return view('home');
    }
}
