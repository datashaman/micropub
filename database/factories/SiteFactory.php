<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Site;
use App\User;
use Faker\Generator as Faker;

$factory->define(Site::class, function (Faker $faker) {
    return [
        'url' => $faker->url,
        'user_id' => factory(User::class)->lazy(),
    ];
});
