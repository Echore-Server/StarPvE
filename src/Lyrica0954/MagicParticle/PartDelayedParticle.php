<?php

declare(strict_types=1);

namespace Lyrica0954\MagicParticle;

use Lyrica0954\StarPvE\utils\TaskUtil;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\Packet;
use pocketmine\network\mcpe\protocol\SpawnParticleEffectPacket;
use pocketmine\network\mcpe\protocol\types\DimensionIds;
use pocketmine\player\Player;
use pocketmine\world\particle\Particle;
use pocketmine\world\Position;

class PartDelayedParticle extends CoveredParticle {

	private int $offset;

	private array $drawHooks;
	private array $partHooks;

	public function __construct(
		CoveredParticle $particle,
		private int $period,
		private int $partLength = 1,
		private bool $reverse = false
	) {
		$this->offset = 0;
		$this->pos = $particle->getPosition();
		$this->drawHooks = [];
		$this->partHooks = [];
		if ($reverse) {
			$this->addPartHook(function (array $parts): array {
				return array_reverse($parts);
			});
		}
		$this->particle = $particle->getParticle();
	}

	public function getOffset(): int {
		return $this->offset;
	}

	public function getPartLength(): int {
		return $this->partLength;
	}

	public function getPeriod(): int {
		return $this->period;
	}

	public function isReverse(): bool {
		return $this->reverse;
	}

	public function addDrawHook(\Closure $closure) {
		$this->drawHooks[] = $closure;
	}

	public function addPartHook(\Closure $closure) {
		$this->partHooks[] = $closure;
	}

	public function getPartHooks(): array {
		return $this->partHooks;
	}

	public function getDrawHooks(): array {
		return $this->drawHooks;
	}

	public function draw(): array {
		return $this->particle->drawAsDelayed($this->pos);
	}

	public function getPackets(ParticleOption $option): array {
		$packets = [];
		$generator = $this->draw($this->pos);
		foreach ($generator as $particlePos) {
			if (is_string($option->getParticle())) {
				$pkt = SpawnParticleEffectPacket::create(DimensionIds::OVERWORLD, -1, $particlePos, $option->getParticle(), $option->getMolang());
				$pk = [$pkt];
			} elseif ($option->getParticle() instanceof Particle) {
				$pk = $option->getParticle()->encode($particlePos);
			}
			$packets[] = $pk;
		}

		return $packets;
	}
}
