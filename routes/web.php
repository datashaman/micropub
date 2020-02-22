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
    ->group(
        function () {
            Route::get('micropub/', 'MicropubController@query');
            Route::post('micropub/', 'MicropubController@post');
        }
    );

Route::get('indieauth/login', 'IndieAuthController@login')->name('indieauth.login');
Route::post('indieauth/login', 'IndieAuthController@doLogin')->name('indieauth.do-login');
Route::get('indieauth/logout', 'IndieAuthController@logout')->name('indieauth.logout');
Route::get('indieauth/callback', 'IndieAuthController@callback')->name('indieauth.callback');

Route::get('github/login', 'GithubController@login')->name('github.login');
Route::get('github/logout', 'GithubController@logout')->name('github.logout');
Route::get('github/callback', 'GithubController@callback');
