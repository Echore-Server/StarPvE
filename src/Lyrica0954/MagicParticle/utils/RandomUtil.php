<?php

declare(strict_types=1);

namespace Lyrica0954\MagicParticle\utils;

use pocketmine\math\Vector3;

class RandomUtil {

	public static function vector3(float $min, float $max): Vector3 {
		return new Vector3(
			self::float($min, $max),
			self::float($min, $max),
			self::float($min, $max)
		);
	}

	public static function float(float $min, float $max) {
		return $min + mt_rand() / mt_getrandmax() * ($max - $min);
	}
}
