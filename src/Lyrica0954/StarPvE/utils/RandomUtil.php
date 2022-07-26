<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\utils;

class RandomUtil {

    public static function rand_float(Float $min, Float $max) {
        return $min + mt_rand() / mt_getrandmax() * ($max - $min);
    }

    public static function percentage(float $percentage, float $max = 100.0): bool {
        $rand = self::rand_float(0, $max);
        return $rand < ($percentage * $max);
    }
}
