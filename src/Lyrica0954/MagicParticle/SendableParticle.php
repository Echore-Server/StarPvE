<?php

declare(strict_types=1);

namespace Lyrica0954\MagicParticle;

use Generator;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\LevelEventPacket;
use pocketmine\network\mcpe\protocol\SpawnParticleEffectPacket;
use pocketmine\network\mcpe\protocol\types\DimensionIds;
use pocketmine\player\Player;
use pocketmine\world\particle\Particle;
use pocketmine\world\Position;

abstract class SendableParticle implements DrawableParticle {

	public function drawAsDelayed(Position $pos): array {
		return $this->draw($pos);
	}

	public function getPackets(Position $pos, ParticleOption $option): array {
		$packets = [];
		$generator = $this->draw($pos);
		foreach ($generator as $particlePos) {
			if (is_string($option->getParticle())) {
				$pkt = SpawnParticleEffectPacket::create(DimensionIds::OVERWORLD, -1, $particlePos, $option->getParticle(), $option->getMolang());
				$pk = [$pkt];
			} else {
				$pk = $option->getParticle()->encode($particlePos);
			}
			$packets[] = $pk;
		}

		return $packets;
	}
}
