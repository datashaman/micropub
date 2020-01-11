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

Route::get('', 'HomeController@index')->name('home');

Route::get('login', 'AuthController@login')->name('auth.login');
Route::post('login', 'AuthController@doLogin');

Route::get('callback', 'AuthController@callback')->name('auth.callback');
