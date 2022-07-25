<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\utils;

class RandomUtil {

    public static function rand_float(Float $min, Float $max) {
        return $min + mt_rand() / mt_getrandmax() * ($max - $min);
    }
}
