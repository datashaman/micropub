<?php

namespace App\Http\Middleware;

use Closure;

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
        $accessToken = $request->get('access_token');

        $client = new Client();

        $user = $client->request('GET', config('indieauth.tokenEndpoint'), [
            'headers' => [
                'Authorization' => "Bearer $accessToken",
            ],
        ]);

        if (!in_array($user['me'], config('indieauth.me'))) {
            abort(403);
        }

        $request->session()->put('user', $user);

        return $next($request);
    }
}
