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

	public function addDrawHook(\Closure $closure) {
		$this->drawHooks[] = $closure;
	}

	public function addPartHook(\Closure $closure) {
		$this->partHooks[] = $closure;
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

	public function sendToPlayer(Player $player, ParticleOption $option) {
		$this->sendPartsToPlayers([$player], $this->getPackets($option));
	}

	public function sendToPlayers(array $players, ParticleOption $option): void {
		$this->sendPartsToPlayers($players, $this->getPackets($option));
	}

	/**
	 * @param Player[] $player
	 * @param array{k: int, packets: array{k: int, packet: Packet}} $parts
	 * 
	 * @return void
	 */
	protected function sendPartsToPlayers(array $players, array $parts): void {
		foreach ($this->partHooks as $hook) {
			$parts = ($hook)($parts);
		}

		$limit = (int) floor(count($parts) / $this->partLength);
		TaskUtil::repeatingClosureLimit(function () use ($players, $parts) {
			$this->offset += $this->partLength;
			$slicedPart = array_slice($parts, $this->offset, $this->partLength);
			foreach ($slicedPart as $packed) {
				/**
				 * @var Packet[] $packed
				 */
				foreach ($packed as $pk) {
					foreach ($this->drawHooks as $hook) {
						($hook)($pk->position);
					}
					foreach ($players as $player) {
						if ($player instanceof Player && $player->isOnline()) {
							if ($this->filter($player, $pk->position)) {
								$player->getNetworkSession()->addToSendBuffer($pk);
							}
						}
					}
				}
			}
		}, $this->period, $limit);
	}

	protected function filter(Player $player, Vector3 $pos, float $maxRange = 20): ?Vector3 {
		return ($player->canInteract($pos, $maxRange, M_SQRT3 / 3)) ? $pos : null;
	}
}
