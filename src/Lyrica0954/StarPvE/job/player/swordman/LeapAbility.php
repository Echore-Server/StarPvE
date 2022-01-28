<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\job\player\swordman;

use Lyrica0954\StarPvE\game\wave\MonsterData;
use Lyrica0954\StarPvE\job\Ability;
use Lyrica0954\StarPvE\job\ActionResult;
use Lyrica0954\StarPvE\job\ticking\Ticking;
use Lyrica0954\StarPvE\job\ticking\TickingController;
use Lyrica0954\StarPvE\utils\EntityUtil;
use Lyrica0954\StarPvE\utils\PlayerUtil;
use Lyrica0954\StarPvE\utils\VectorUtil;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\network\mcpe\protocol\LevelEventPacket;
use pocketmine\network\mcpe\protocol\types\ActorEvent;
use pocketmine\network\mcpe\protocol\types\ParticleIds;
use pocketmine\world\particle\ExplodeParticle;

class LeapAbility extends Ability implements Ticking{
    use TickingController;

    private array $damaged = [];

    private bool $activeMotion = false;

    public function getCooltime(): int{
        return (6 * 20);
    }

    protected function onActivate(): ActionResult{
        $motion = VectorUtil::getDirectionHorizontal($this->player->getLocation()->yaw);
        if ($this->player->isOnGround()){
            $motion = $motion->multiply(3.5);
            $motion->y = 0.6;
        } else {
            $motion = $motion->multiply(1.8);
            $motion->y = 1.05;
        }
        $this->player->setMotion($motion);
        $this->startTicking("leapDamage", 1);

        return ActionResult::SUCCEEDED();
    }

    public function onTick(string $id, int $tick): void{
        if ($id === "leapDamage"){
            if ($this->activeMotion){
                if ($this->player->isOnGround() || $tick >= 45 || $this->player->getFallDistance() >= 2.5){
                    $this->activeMotion = false;
                    $this->damaged = [];
                    $this->stopTicking($id);
                } else {
                    PlayerUtil::broadcastSound($this->player->getPosition(), "ui.cartography_table.take_result", 1.15, 0.9);
    
                    $particle = new ExplodeParticle();
                    $this->player->getWorld()->addParticle($this->player->getPosition(), $particle);
    
                    foreach(EntityUtil::getWithinRange($this->player->getPosition(), 3.5) as $entity){
                        if (MonsterData::isMonster($entity)){
                            $hash = spl_object_hash($entity);
                            if (!in_array($hash, $this->damaged, true)){
                                $this->damaged[] = $hash;
                                $xz = 2.5;
                                $y = 1.1;
                                if (false){
                                    $xz = 0.9;
                                    $y = 1.0;
                                }
    
                                $source = new EntityDamageByEntityEvent($this->player, $entity, EntityDamageByEntityEvent::CAUSE_ENTITY_ATTACK, 3.0);
                                $source->setAttackCooldown(0);
    
                                EntityUtil::attackEntity($source, $xz, $y);
                            }
                        }
                    }
                }
            } else {
                if (!$this->player->isOnGround()){
                    $this->activeMotion = true;
                }

                if ($tick >= 20){
                    $this->stopTicking($id);
                }
            }
        }
    }
}