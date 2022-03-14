<?php

declare(strict_types=1);

namespace Lyrica0954\MagicParticle;

use Lyrica0954\StarPvE\utils\TaskUtil;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\Packet;
use pocketmine\network\mcpe\protocol\SpawnParticleEffectPacket;
use pocketmine\player\Player;
use pocketmine\world\particle\Particle;
use pocketmine\world\Position;

class PartDelayedParticle extends CoveredParticle {

	private int $offset;

	private array $drawHooks;

	public function __construct(
		CoveredParticle $particle,
		private int $period,
		private int $partLength = 1,
		private bool $reverse = false
	)
	{
		$this->offset = 0;
		$this->pos = $particle->getPosition();
		$this->drawHooks = [];
		$this->particle = $particle->getParticle();
	}

	public function addDrawHook(\Closure $closure){
		$this->drawHooks[] = $closure;
	}

	public function draw(): array{
		return $this->particle->drawAsDelayed($this->pos);
	}

	public function getPackets(string|Particle $particle): array{
        $packets = [];
        $generator = $this->draw($this->pos);
        foreach($generator as $particlePos){
            if (is_string($particle)){
                $pkt = new SpawnParticleEffectPacket;
                $pkt->particleName = $particle;
                $pkt->position = $particlePos;
                $pk = [$pkt];
            } elseif ($particle instanceof Particle) {
                $pk = $particle->encode($particlePos);
            }
            $packets[] = $pk;
        }

        return $packets;
    }

	public function sendToPlayer(Player $player, string|Particle $particle){
		$this->sendPartsToPlayers([$player], $this->getPackets($particle));
    }

	public function sendToPlayers(array $players, string|Particle $particle): void{
		$this->sendPartsToPlayers($players, $this->getPackets($particle));
	}

	/**
	 * @param Player[] $player
	 * @param array{k: int, packets: array{k: int, packet: Packet}} $parts
	 * 
	 * @return void
	 */
	protected function sendPartsToPlayers(array $players, array $parts): void{
		$parts = ($this->reverse) ? array_reverse($parts) : $parts;
		$limit = (integer) floor(count($parts) / $this->partLength);
		TaskUtil::repeatingClosureLimit(function() use($players, $parts){
			$this->offset += $this->partLength;
			$slicedPart = array_slice($parts, $this->offset, $this->partLength);
			foreach($slicedPart as $packed){
				/**
				 * @var Packet[] $packed
				 */
				foreach($packed as $pk){
					foreach($this->drawHooks as $hook){
						($hook)($pk->position);
					}
					foreach($players as $player){
						if ($player instanceof Player && $player->isOnline()){
							if ($this->filter($player, $pk->position)){
								$player->getNetworkSession()->addToSendBuffer($pk);
							}
						}
					}
				}
			}
		}, $this->period, $limit);
	}

	protected function filter(Player $player, Vector3 $pos, float $maxRange = 20): ?Vector3{
        return ($player->canInteract($pos, $maxRange, M_SQRT3/3)) ? $pos : null;
    }
}