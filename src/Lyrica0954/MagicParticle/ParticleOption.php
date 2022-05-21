<?php

declare(strict_types=1);

namespace Lyrica0954\MagicParticle;

use pocketmine\network\mcpe\protocol\Packet;
use pocketmine\world\particle\Particle;

class ParticleOption {

	protected string $molang;

	protected string|Particle $particle;

	public function __construct(string|Particle $particle, string $molang) {
		$this->particle = $particle;

		$this->molang = $molang;
	}

	public function getParticle(): string|Particle {
		return $this->particle;
	}

	public function getMolang(): string {
		return $this->molang;
	}


	public static function levelEvent(Particle $particle): self {
		$option = new ParticleOption($particle, "");
		return $option;
	}

	public static function spawnPacket(string $particle, string $molang): self {
		$option = new ParticleOption($particle, $molang);
		return $option;
	}
}
