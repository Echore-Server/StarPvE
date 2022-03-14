<?php

declare(strict_types=1);

namespace Lyrica0954\MagicParticle\effect;

use Lyrica0954\MagicParticle\CoveredParticle;
use Lyrica0954\MagicParticle\PartDelayedParticle;
use Lyrica0954\MagicParticle\SendableParticle;
use pocketmine\player\Player;
use pocketmine\world\particle\Particle;
use pocketmine\world\Position;

abstract class ParticleEffect{

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
	public function drawAsDelayed(Position $pos): array{
		return $this->draw($pos);
	}

	public function sendToPlayer(Player $player, Position $pos, string|Particle $particle){
		foreach($this->draw($pos) as $coveredParticle){
			if ($coveredParticle instanceof PartDelayedParticle){
				$coveredParticle->sendToPlayer($player, $particle);
			} else {
				$coveredParticle->getParticle()->sendToPlayer($player, $coveredParticle->getPosition(), $particle);
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
	public function sendToPlayers(array $players, Position $pos, string|Particle $particle): void{
		foreach($this->draw($pos) as $coveredParticle){
			if ($coveredParticle instanceof CoveredParticle){
				if ($coveredParticle instanceof PartDelayedParticle){
					$coveredParticle->sendToPlayers($players, $particle);
				} else {
					$coveredParticle->getParticle()->sendToPlayers($players, $coveredParticle->getPosition(), $particle);
				}
			}
		}
	}
}