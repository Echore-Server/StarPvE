<?php

declare(strict_types=1);

namespace Lyrica0954\MagicParticle;

use Lyrica0954\MagicParticle\effect\ParticleEffect;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\ClientboundPacket;
use pocketmine\network\mcpe\protocol\Packet;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\Task;
use pocketmine\Server;
use pocketmine\world\Position;

class ParticleSender {

	protected PluginBase $plugin;

	public function __construct(PluginBase $plugin) {
		$this->plugin = $plugin;
	}

	public function check(Player $player, Vector3 $pos): ?bool {
		return true;
	}

	public function send(Player $player, Vector3 $pos, ClientboundPacket $packet) {
		if ($this->check($player, $pos)) {
			$this->onSend($player, $pos, $packet);
			$player->getNetworkSession()->addToSendBuffer($packet);
		}
	}

	protected function onSend(Player $player, Vector3 $pos, ClientboundPacket $packet) {
	}

	public function sendParticle(SendableParticle $particle, array $players, Position $pos, ParticleOption $option) {
		foreach ($particle->getPackets($pos, $option) as $packed) {
			foreach ($packed as $pk) {
				foreach ($players as $player) {
					if ($player instanceof Player) {
						$this->send($player, $pk->position, $pk);
					}
				}
			}
		}
	}

	public function sendEffect(ParticleEffect $effect, array $players, Position $pos, ParticleOption $option) {
		foreach ($effect->draw($pos) as $coveredParticle) {
			if ($coveredParticle instanceof CoveredParticle) {
				if ($coveredParticle instanceof PartDelayedParticle) {
					$this->sendPartDelayed($coveredParticle, $players, $option);
				} else {
					$particle = $coveredParticle->getParticle();
					$this->sendParticle($particle, $players, $coveredParticle->getPosition(), $option);
				}
			}
		}
	}

	public function sendPartDelayed(PartDelayedParticle $particle, array $players, ParticleOption $option) {
		$parts = $particle->getPackets($option);

		foreach ($particle->getPartHooks() as $hook) {
			$parts = ($hook)($parts);
		}

		$progress = new \stdClass;
		$progress->offset = $particle->getOffset();

		$limit = (int) floor(count($parts) / $particle->getPartLength());

		$closure = function () use ($players, $parts, $particle, $progress) {
			$partLength = $particle->getPartLength();

			$progress->offset += $partLength;
			$slicedPart = array_slice($parts, $progress->offset, $partLength);
			foreach ($slicedPart as $packed) {
				/**
				 * @var Packet[] $packed
				 */
				foreach ($packed as $pk) {
					foreach ($particle->getDrawHooks() as $hook) {
						($hook)($pk->position);
					}
					foreach ($players as $player) {
						if ($player instanceof Player && $player->isOnline()) {
							$this->send($player, $pk->position, $pk);
						}
					}
				}
			}
		};
		$task = new class($closure, $limit) extends Task {

			private \Closure $closure;
			private int $limit;
			private int $count;

			public function __construct(\Closure $closure, int $limit) {
				$this->closure = $closure;
				$this->limit = $limit;
				$this->count = 0;
			}

			public function onRun(): void {
				$this->count++;
				($this->closure)();

				if ($this->count >= $this->limit) {
					$this->getHandler()->cancel();
				}
			}
		};

		$this->plugin->getScheduler()->scheduleRepeatingTask($task, $particle->getPeriod());
	}
}
