<?php

namespace App\Http\Middleware;

use App\Site;
use Closure;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Auth;
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
        $accessToken = $request->get('access_token') ?: $request->bearerToken();

        if (empty($accessToken)) {
            Log::warning('Micropub request without access token');

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

        Log::info('Micropub Authenticated', compact('user'));

        $site = Site::query()
            ->where('url', $user['me'])
            ->first();

        if (!$site) {
            Log::warning('Site not found', ['url' => $user['me']]);

            return response()->json(['error' => 'forbidden'], 403);
        }

        Log::info('Micropub site', ['site' => $site]);
        $request->merge(['site' => $site]);

        return $next($request);
    }
}
