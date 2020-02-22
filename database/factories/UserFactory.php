<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */
use App\User;
use Faker\Generator as Faker;
use Illuminate\Support\Str;

/*
|--------------------------------------------------------------------------
| Model Factories
|--------------------------------------------------------------------------
|
| This directory should contain each of the model factory definitions for
| your application. Factories provide a convenient way to generate new
| model instances for testing / seeding your application's database.
|
*/

$factory->define(User::class, function (Faker $faker) {
    return [
        'provider' => 'github',
        'provider_id' => $faker->randomNumber(),
        'nickname' => $faker->username,
        'name' => $faker->name,
        'email' => $faker->unique()->safeEmail,
        'avatar' => $faker->imageUrl(),
        'token' => Str::random(64),
    ];
});
