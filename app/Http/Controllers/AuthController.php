<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use Illuminate\Http\Request;
use IndieAuth\Client;

class AuthController extends Controller
{
    public function __construct()
    {
        session_start();

        Client::$clientID = route('home');
        Client::$redirectURL = route('auth.callback');
    }

    public function login()
    {
        return view('login');
    }

    public function doLogin(LoginRequest $request)
    {
        [$authURL, $error] = Client::begin($request['url'], 'create update');

        if ($error) {
            dd($error);
        }

        return redirect()->to($authURL);
    }

    public function callback(Request $request)
    {
        [$user, $error] = Client::complete($request->all());

        if ($error) {
            dd($error);
        }

        $request->session()->put('user', $user);

        return redirect()->route('home');
    }

    public function logout(Request $request)
    {
        $request->session()->forget('user');

        return redirect()->route('home');
    }
}
