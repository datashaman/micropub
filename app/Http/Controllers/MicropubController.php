<?php

namespace App\Http\Controllers;

use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MicropubController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     *
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request)
    {
        $accessToken = $request->get('access_token');

        if (!$accessToken) {
            abort(403);
        }

        $client = new Client();

        $user = $client->request('GET', config('indieauth.tokenEndpoint'), [
            'headers' => [
                'Authorization' => "Bearer $accessToken",
            ],
        ]);

        if (!in_array($user['me'], config('indieauth.me'))) {
            abort(403);
        }

        Log::debug('Micropub request', $request->all());
    }
}
