<?php

namespace App\Http\Controllers;

use Exception;
use GrahamCampbell\GitHub\GitHubFactory;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\File;
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

        $repository = $crawler
            ->filter('head link[rel="content-repository"]')
            ->attr('href');

        if (!$repository) {
            $repository = $crawler
                ->filter('head link[rel="code-repository"]')
                ->attr('href');
        }

        $url = parse_url($repository);

        if ($url['host'] !== 'github.com') {
            throw new Exception('Only GitHub repositories are supported (for now)');
        }

        dd([
            'basename' => File::basename($url['path']),
            'filename' => File::filename($url['path']),
            'extension' => File::extension($url['path']),
        ]);

        return view('home');
    }
}
