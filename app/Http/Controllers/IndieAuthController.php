<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Site;
use GuzzleHttp\Client as GuzzleClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use IndieAuth\Client;

class IndieAuthController extends Controller
{
    public function __construct()
    {
        session_start();

        Client::$clientID = route('home');
        Client::$redirectURL = route('indieauth.callback');
    }

    public function login()
    {
        return view('login');
    }

    public function doLogin(LoginRequest $request)
    {
        [$authURL, $error] = Client::begin($request['url'], 'create update');

        if ($error) {
            Log::error('error', $error);

            return redirect()
                ->back()
                ->withError($error);
        }

        return redirect()->to($authURL);
    }

    public function callback(Request $request)
    {
        [$user, $error] = Client::complete($request->all());

        if ($error) {
            Log::error('error', $error);

            return redirect()
                ->back()
                ->withError($error);
        }

        $url = $this->getRepository($user['me']);
        $parts = parse_url($url);

        if ($parts['host'] !== 'github.com') {
            throw new Exception('Only GitHub repositories are supported (for now)');
        }

        $owner = trim(File::dirname($parts['path']), '/');
        $repo = File::name($parts['path']);
        $branch = Arr::get($parts, 'fragment', 'master');

        $site = Site::updateOrCreate(
            [
                'url' => $user['me'],
            ],
            [
                'owner' => $owner,
                'repo' => $repo,
                'branch' => $branch,
            ]
        );

        $request->session()->put('site', $site);

        return redirect()->route('home');
    }

    public function logout(Request $request)
    {
        $request->session()->forget('site');

        return redirect()->route('home');
    }

    protected function getRepository(string $url): string
    {
        $client = new GuzzleClient(
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

        return $url;
    }
}
