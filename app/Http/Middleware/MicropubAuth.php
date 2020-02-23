<?php

namespace App\Http\Middleware;

use Closure;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class MicropubAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     *
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        Log::debug('Request', ['all' => $request->all()]);

        $accessToken = $request->get('access_token') ?: $request->bearerToken();
        Log::debug('Access token', compact('accessToken'));

        if (empty($accessToken)) {
            return response()->json(['error' => 'unauthorized'], 401);
        }

        $client = new Client();

        $response = $client->request('GET', config('indieauth.tokenEndpoint'), [
            'headers' => [
                'Accept' => 'application/json',
                'Authorization' => "Bearer $accessToken",
            ],
        ]);

        $body = (string) $response->getBody();
        $user = json_decode($body, true);

        Log::debug('IndieAuth user', compact('user'));

        if (!in_array($user['me'], config('indieauth.authorized'))) {
            return response()->json(['error' => 'forbidden'], 403);
        }

        $request->session()->put('user', $user);

        return $next($request);
    }
}
