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

Route::get('/', 'HomeController@index')->name('home');

Route::middleware(['auth.micropub'])
    ->prefix('micropub')
    ->group(
        function () {
            Route::get('/', 'MicropubController@query');
            Route::post('/', 'MicropubController@post');
        }
    );

Route::middleware(['auth'])
    ->name('indieauth.')
    ->prefix('indieauth')
    ->group(
        function () {
            Route::get('login', 'IndieAuthController@login')->name('login');
            Route::post('login', 'IndieAuthController@doLogin')->name('do-login');
            Route::get('logout', 'IndieAuthController@logout')->name('logout');
            Route::get('callback', 'IndieAuthController@callback')->name('callback');
        }
    );

Route::get('github/login', 'GithubController@login')->name('github.login');
Route::get('github/logout', 'GithubController@logout')->name('github.logout');
Route::get('github/callback', 'GithubController@callback');

Route::resource('sites', 'SiteController');
