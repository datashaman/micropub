<?php

namespace App\Http\Controllers;

use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use p3k\Micropub\Request as MicropubRequest;

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
        $all = $request->all();

        Log::debug('Request', $all);

        $mpRequest = MicropubRequest::create($all);

        Log::debug('Micropub request', $mpRequest->toMf2());
    }
}
