<?php

declare(strict_types=1);

namespace Lyrica0954\MagicParticle\effect;

use Lyrica0954\MagicParticle\CoveredParticle;
use Lyrica0954\MagicParticle\PartDelayedParticle;
use Lyrica0954\MagicParticle\ParticleOption;
use Lyrica0954\MagicParticle\SendableParticle;
use pocketmine\player\Player;
use pocketmine\world\particle\Particle;
use pocketmine\world\Position;

abstract class ParticleEffect {

	/**
	 * @param Position $pos
	 * 
	 * @return CoveredParticle[]
	 */
	abstract public function draw(Position $pos): array;

	/**
	 * @param Position $pos
	 * 
	 * @return CoveredParticle[]
	 */
	public function drawAsDelayed(Position $pos): array {
		return $this->draw($pos);
	}
}
