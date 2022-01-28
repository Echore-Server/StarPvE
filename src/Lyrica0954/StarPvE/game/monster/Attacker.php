<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\game\monster;

use Lyrica0954\SmartEntity\entity\fightstyle\FollowStyle;
use Lyrica0954\SmartEntity\entity\fightstyle\MeleeStyle;
use Lyrica0954\SmartEntity\entity\fightstyle\Style;
use Lyrica0954\SmartEntity\entity\ManageableEntity;
use Lyrica0954\SmartEntity\entity\Neutral;
use Lyrica0954\SmartEntity\entity\walking\FightingEntity;
use Lyrica0954\StarPvE\entity\Villager;
use Lyrica0954\StarPvE\game\Game;
use Lyrica0954\StarPvE\StarPvE;
use Lyrica0954\StarPvE\utils\HealthBarEntity;
use pocketmine\entity\Attribute;
use pocketmine\entity\Entity;
use pocketmine\entity\EntitySizeInfo;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;

class Attacker extends FightingEntity implements Neutral {
    use HealthBarEntity;

    protected float $reach = 1.2;

    public static function getNetworkTypeId(): string{
        return EntityIds::WITCH;
    }

    public function checkTarget(Entity $entity, float $range): bool{
        return ($entity instanceof Villager) && $range <= $this->getFollowRange();
    }

    public function checkCurrentTarget(){
        return true;
    }

    public function attack(EntityDamageEvent $source): void{
        parent::attack($source);

        $game = StarPvE::getInstance()->getGameManager()->getGameFromWorld($this->getWorld());
        if ($game instanceof Game){
            $this->target = $game->getVillager();
        }
    }

    protected function initEntity(CompoundTag $nbt): void{
        parent::initEntity($nbt);

        $game = StarPvE::getInstance()->getGameManager()->getGameFromWorld($this->getWorld());
        if ($game instanceof Game){
            $this->target = $game->getVillager();
        } else {
            $this->close();
        }

        $this->getAttributeMap()->get(Attribute::KNOCKBACK_RESISTANCE)->setValue(1.0);
    }

    protected function selectTarget(array $targets): void{
    }

    public function avoidCollidingEntities(){
        #unti!
    }

    protected function getInitialSizeInfo(): EntitySizeInfo{
        return new EntitySizeInfo(1.6, 0.8);
    }

    protected function getInitialFightStyle(): Style{
        return new MeleeStyle($this);
    }

    public function getAddtionalAttackCooldown(): int{
        return 40;
    }

    public function moveForward(){
        if (!$this->isImmobile()){
            parent::moveForward();
        }
    }

    protected function onTick(int $currentTick): void{
        if ($this->isInAttackRange($this->target)){
            $this->setImmobile(true);
        } else {
            $this->setImmobile(false);
        }
    }


    public function getFollowRange(): float{
        return 100;
    }

    public function getName(): string{
        return "Attacker";
    }
}