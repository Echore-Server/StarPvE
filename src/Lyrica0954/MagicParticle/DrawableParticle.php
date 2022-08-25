<?php

declare(strict_types=1);

namespace Lyrica0954\MagicParticle;

use Generator;
use pocketmine\world\Position;
use pocketmine\math\Vector3;

interface DrawableParticle {

	/**
	 * @param Position $pos
	 * 
	 * @return Vector3[]
	 */
	public function draw(Position $pos): array;

	/**
	 * @param Position $pos
	 * 
	 * @return Vector3[]
	 */
	public function drawAsDelayed(Position $pos): array;
}
