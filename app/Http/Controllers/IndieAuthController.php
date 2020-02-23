<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Site;
use Exception;
use GuzzleHttp\Client as GuzzleClient;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use IndieAuth\Client;
use Symfony\Component\DomCrawler\Crawler;

class IndieAuthController extends Controller
{
    protected $guzzle;

    public function __construct()
    {
        session_start();

        Client::$clientID = route('home');
        Client::$redirectURL = route('indieauth.callback');

        $this->guzzle = new GuzzleClient(
            [
                'connect_timeout' => 2.0,
                'timeout' => 4.0,
            ]
        );
    }

    public function login(LoginRequest $request)
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

        $links = $this->getLinks($user['me']);

        Log::debug('Links', ['links' => $links->all()]);

        if ($links->get('micropub') !== route('micropub.query')) {
            throw new Exception('micropub link must be set to ' . route('micropub.query') . ' to use this service');
        }

        $repository = $links->get('content-repository', $links->get('code-repository'));

        if (!$repository) {
            throw new Exception('content-repository or code-repository link must be set to use this service');
        }

        $parts = parse_url($repository);

        if ($parts['host'] !== 'github.com') {
            throw new Exception('content-repository or code-repository link must point to a GitHub repository');
        }

        if ($links->get('token_endpoint') != config('indieauth.tokenEndpoint')) {
            throw new Exception('token_endpoint link must be set to ' . config('indieauth.tokenEndpoint') . ' to use this service');
        }

        $owner = trim(File::dirname($parts['path']), '/');
        $repo = File::name($parts['path']);
        $branch = Arr::get($parts, 'fragment', 'master');

        Site::updateOrCreate(
            [
                'user_id' => auth()->user()->id,
                'url' => $user['me'],
            ],
            [
                'owner' => $owner,
                'repo' => $repo,
                'branch' => $branch,
                'token_endpoint' => $links->get('token_endpoint'),
            ]
        );

        return redirect()->route('home');
    }

    protected function getLinks(string $me): Collection
    {
        $response = $this->guzzle->get($me);
        $crawler = new Crawler((string) $response->getBody());

        $links = $crawler
            ->filter('head link[rel]')
            ->extract(['rel', 'href']);

        return collect($links)
            ->mapWithKeys(
                function ($link) {
                    return [
                        $link[0] => $link[1],
                    ];
                }
            );
    }
}
