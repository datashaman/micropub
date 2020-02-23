<?php

namespace App\Http\Controllers;

use Exception;
use GrahamCampbell\GitHub\GitHubFactory;
use GuzzleHttp\Client;
use Illuminate\Support\Arr;
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

        $url = $crawler
            ->filter('head link[rel="content-repository"]')
            ->attr('href');

        if (!$url) {
            $url = $crawler
                ->filter('head link[rel="code-repository"]')
                ->attr('href');
        }

        $parts = parse_url($url);

        if ($parts['host'] !== 'github.com') {
            throw new Exception('Only GitHub repositories are supported (for now)');
        }

        $owner = trim(File::dirname($parts['path']), '/');
        $repo = File::name($parts['path']);
        $branch = Arr::get($parts, 'fragment', 'master');

        return view(
            'home',
            [
                'url' => $url,
                'owner' => $owner,
                'repo' => $repo,
                'branch' => $branch,
            ]
        );
    }
}
