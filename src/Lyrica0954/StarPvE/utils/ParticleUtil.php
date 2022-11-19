<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\utils;

use Lyrica0954\MagicParticle\effect\PartDelayedEffect;
use Lyrica0954\MagicParticle\effect\ParticleEffect;
use Lyrica0954\MagicParticle\PartDelayedParticle;
use Lyrica0954\MagicParticle\ParticleOption;
use Lyrica0954\MagicParticle\SendableParticle;
use Lyrica0954\MagicParticle\utils\MolangUtil;
use Lyrica0954\StarPvE\StarPvE;
use pocketmine\color\Color;
use pocketmine\math\Vector3;
use pocketmine\world\Position;

class ParticleUtil {

	public static function send(PartDelayedParticle|PartDelayedEffect|SendableParticle|ParticleEffect $particle, array $players, ?Position $pos = null, ParticleOption $option) {
		$sender = StarPvE::getInstance()->getParticleHost()->getSender();

		if (!$particle instanceof PartDelayedParticle && $pos === null) {
			throw new \Exception("pos null not allowed");
		}

		if ($particle instanceof ParticleEffect) { #PartDelayedEffect, ParticleEffect
			$sender->sendEffect($particle, $players, $pos, $option);
		} elseif ($particle instanceof PartDelayedParticle) {
			$sender->sendPartDelayed($particle, $players, $option);
		} elseif ($particle instanceof SendableParticle) {
			$sender->sendParticle($particle, $players, $pos, $option);
		}
	}

	public static function circleMolang(float $lifetime, int $amount, float $radius, Color $color, Vector3 $plane) {
		$molang = [];
		$molang[] = MolangUtil::variable("lifetime", $lifetime);
		$molang[] = MolangUtil::variable("amount", $amount);
		$molang[] = MolangUtil::member("color", [
			["r", $color->getR() / 255],
			["g", $color->getG() / 255],
			["b", $color->getB() / 255],
			["a", $color->getA() / 255]
		]);

		$molang[] = MolangUtil::member("plane", [
			["x", $plane->x],
			["y", $plane->y],
			["z", $plane->z]
		]);


		$molang[] = MolangUtil::variable("radius", $radius);

		return $molang;
	}

	public static function motionCircleMolang(array $circleMolang, float $speed, float $airDrag = 0.0, float $accelY = 0.0) {
		$circleMolang[] = MolangUtil::variable("speed", $speed);
		$circleMolang[] = MolangUtil::variable("drag", $airDrag);
		$circleMolang[] = MolangUtil::variable("accely", $accelY);

		return $circleMolang;
	}
}
