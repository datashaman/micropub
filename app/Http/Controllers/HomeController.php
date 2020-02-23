<?php

namespace App\Http\Controllers;

use DOMDocument;
use GrahamCampbell\GitHub\GitHubFactory;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Socialite;
use Symfony\Component\DomCrawler\Crawler;

class HomeController extends Controller
{
    public function index()
    {
        $me = session('user.me');

        $client = new Client(
            [
                'connect_timeout' => 2.0,
                'timeout' => 4.0,
            ]
        );

        $response = $client->get($me);
        $crawler = new Crawler((string) $response->getBody());
        $links = $crawler->filter('head link[rel]')->extract(['rel', 'href']);

        dd($links);

        return view('home');
    }
}
