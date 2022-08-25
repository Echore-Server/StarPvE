<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\utils;

class MathUtil {

	public static function clamp(float $n, float $min, float $max): float {
		return min($max, max($min, $n));
	}
}
