<?php

use Symfony\Component\Yaml\Yaml;

if (!function_exists('yaml_dump')) {
    function yaml_dump(...$args) {
        return trim(Yaml::dump(...$args));
    }
}
