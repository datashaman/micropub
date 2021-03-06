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
    ->name('micropub.')
    ->prefix('micropub')
    ->group(
        function () {
            Route::get('/', 'MicropubController@query')->name('query');
            Route::post('/', 'MicropubController@post')->name('post');
        }
    );

Route::middleware(['auth'])
    ->group(
        function () {
            Route::name('indieauth.')
                ->prefix('indieauth')
                ->group(
                    function () {
                        Route::post('login', 'IndieAuthController@login')->name('login');
                        Route::get('callback', 'IndieAuthController@callback')->name('callback');
                    }
                );

            Route::resource('sites', 'SiteController');
        }
    );

Route::name('github.')
    ->prefix('github')
    ->group(
        function () {
            Route::get('login', 'GithubController@login')->name('login');
            Route::get('logout', 'GithubController@logout')->name('logout');
            Route::get('callback', 'GithubController@callback')->name('callback');
        }
    );
