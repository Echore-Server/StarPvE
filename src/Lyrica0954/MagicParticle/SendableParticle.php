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

abstract class SendableParticle implements DrawableParticle{

    public function drawAsDelayed(Position $pos): array{
        return $this->draw($pos);
    }
    
    public function getPackets(Position $pos, string|Particle $particle): array{
        $packets = [];
        $generator = $this->draw($pos);
        foreach($generator as $particlePos){
            if (is_string($particle)){
                $pkt = SpawnParticleEffectPacket::create(DimensionIds::OVERWORLD, -1, $particlePos, $particle, "");
                $pk = [$pkt];
            } elseif ($particle instanceof Particle) {
                $pk = $particle->encode($particlePos);
            }
            $packets[] = $pk;
        }

        return $packets;
    }

    public function sendToPlayer(Player $player, Position $pos, string|Particle $particle){
        foreach($this->getPackets($pos, $particle) as $packed){
            foreach($packed as $pk){
                if ($this->filter($player, $pk->position)){
                    $player->getNetworkSession()->addToSendBuffer($pk);
                }
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
        foreach($this->getPackets($pos, $particle) as $packed){
            foreach ($packed as $pk){
                foreach($players as $player){
                    if ($player instanceof Player){
                        if ($this->filter($player, $pk->position)){
                            $player->getNetworkSession()->addToSendBuffer($pk);
                        }
                    }
                }
            }
        }
    }

    protected function filter(Player $player, Vector3 $pos, float $maxRange = 20): ?Vector3{
        return ($player->canInteract($pos, $maxRange, M_SQRT3/3)) ? $pos : null;
    }
} 