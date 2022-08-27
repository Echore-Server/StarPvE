<?php

declare(strict_types=1);

namespace Lyrica0954\MagicParticle;

use pocketmine\network\mcpe\protocol\Packet;
use pocketmine\world\particle\Particle;

class ParticleOption {

	protected string $molang;

	protected string|Particle $particle;

	protected int $actorUniqueId;

	public function __construct(string|Particle $particle, string $molang, int $actorUniqueId = -1) {
		$this->particle = $particle;

		$this->molang = $molang;

		$this->actorUniqueId = $actorUniqueId;
	}

	public function getParticle(): string|Particle {
		return $this->particle;
	}

	public function getMolang(): string {
		return $this->molang;
	}

	public function getActorUniqueId(): int {
		return $this->actorUniqueId;
	}


	public static function levelEvent(Particle $particle, int $actorUniqueId = -1): self {
		$option = new ParticleOption($particle, "", $actorUniqueId);
		return $option;
	}

	public static function spawnPacket(string $particle, string $molang = "", int $actorUniqueId = -1): self {
		$option = new ParticleOption($particle, $molang, $actorUniqueId);
		return $option;
	}
}
