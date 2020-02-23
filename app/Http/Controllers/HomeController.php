<?php

namespace App\Http\Controllers;

use GrahamCampbell\GitHub\GitHubFactory;
use Illuminate\Http\Request;
use function IndieWeb\head_http_rels;
use Socialite;

class HomeController extends Controller
{
    public function index()
    {
        $me = session('user.me');
        $rels = head_http_rels($me);
        dd($rels);

        if (auth()->check()) {
        }

        return view('home');
    }
}
