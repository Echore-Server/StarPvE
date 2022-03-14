<?php

declare(strict_types=1);

namespace Lyrica0954\MagicParticle;

use pocketmine\world\Position;

class CoveredParticle {

	protected SendableParticle $particle;
	protected Position $pos;

	public function __construct(
		SendableParticle $particle,
		Position $pos
	){
		$this->particle = $particle;
		$this->pos = $pos;
	}

	public function getParticle(): SendableParticle{
		return $this->particle;
	}

	public function getPosition(): Position{
		return $this->pos;
	}
}