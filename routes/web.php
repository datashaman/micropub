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

Route::get('indieauth/login', 'IndieAuthController@login')->name('indieauth.login');
Route::post('indieauth/login', 'IndieAuthController@doLogin')->name('indieauth.do-login');
Route::get('indieauth/logout', 'IndieAuthController@logout')->name('indieauth.logout');
Route::get('indieauth/callback', 'IndieAuthController@callback')->name('indieauth.callback');

Route::get('github/redirect', 'GithubController@redirect')->name('github.redirect');
Route::get('github/callback', 'GithubController@callback');
