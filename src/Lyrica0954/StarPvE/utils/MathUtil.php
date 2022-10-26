<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\utils;

class MathUtil {

	public static function clamp(float $n, float $min, float $max): float {
		return min($max, max($min, $n));
	}

	/**
	 * @param float $v
	 * 
	 * @return array<0: string, 1: string>
	 */
	public static function translateAdd(float $v, float $final = 0.0): array {
		$v = $v + $final;
		$op = "+";
		$vs = "増加";
		if ($v < 0.0) {
			$op = "-";
			$vs = "減少";
		}

		return [$vs, $op, abs($v)];
	}

	public static function translatePercentage(float $perc, float $final = 0.0, float $base = 1.0): array {
		$v = $perc + $final;
		$op = "+";
		$vs = "増加";
		if ($v < $base + $final) {
			$op = "-";
			$vs = "減少";
		}

		$v = abs($v - $base);

		return [$vs, $op, $v];
	}
}
