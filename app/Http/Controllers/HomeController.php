<?php

namespace App\Http\Controllers;

use Exception;
use GrahamCampbell\GitHub\GitHubFactory;
use GuzzleHttp\Client;
use Illuminate\Support\Arr;

class HomeController extends Controller
{
    public function index()
    {
        return view(
            'home',
            [
                'site' => session('site'),
            ]
        );
    }
}
