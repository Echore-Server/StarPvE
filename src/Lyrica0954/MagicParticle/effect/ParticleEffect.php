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

	public function sendToPlayer(Player $player, Position $pos, ParticleOption $option) {
		foreach ($this->draw($pos) as $coveredParticle) {
			if ($coveredParticle instanceof PartDelayedParticle) {
				$coveredParticle->sendToPlayer($player, $option);
			} else {
				$coveredParticle->getParticle()->sendToPlayer($player, $coveredParticle->getPosition(), $option);
			}
		}
	}

	/**
	 * @param Player[] $players
	 * @param Position $pos
	 * @param string|Particle $particle
	 * 
	 * @return void
	 */
	public function sendToPlayers(array $players, Position $pos, ParticleOption $option): void {
		foreach ($this->draw($pos) as $coveredParticle) {
			if ($coveredParticle instanceof CoveredParticle) {
				if ($coveredParticle instanceof PartDelayedParticle) {
					$coveredParticle->sendToPlayers($players, $option);
				} else {
					$coveredParticle->getParticle()->sendToPlayers($players, $coveredParticle->getPosition(), $option);
				}
			}
		}
	}
}
