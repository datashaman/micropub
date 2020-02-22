<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', 'HomeController@index')->name('home')->middleware('auth.indie');

Route::middleware(['auth.micropub'])
    ->prefix('micropub')
    ->group(
        function () {
            Route::get('/', 'MicropubController@query');
            Route::post('/', 'MicropubController@post');
        }
    );

Route::get('login', 'AuthController@login')->name('auth.login');
Route::post('login', 'AuthController@doLogin');

Route::get('logout', 'AuthController@logout')->name('auth.logout');

Route::post('entry', 'EntryController@store')->name('entry.store');

Route::get('callback', 'AuthController@callback')->name('auth.callback');

Route::get('login/github', 'LoginController@redirectToProvider')->name('login.github');
Route::get('login/github/callback', 'LoginController@handleProviderCallback');
