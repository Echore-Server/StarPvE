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
use pocketmine\event\entity\EntityDamageByChildEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;
use pocketmine\player\Player;

class Attacker extends FightingEntity implements Neutral {
    use HealthBarEntity;

    protected float $reach = 1.5;

    public static function getNetworkTypeId(): string {
        return EntityIds::WITCH;
    }

    public function checkTarget(Entity $entity, float $range): bool {
        return ($entity instanceof Villager) && $range <= $this->getFollowRange();
    }

    public function checkCurrentTarget() {
        return true;
    }

    public function attack(EntityDamageEvent $source): void {

        if ($source instanceof EntityDamageByChildEntityEvent) {
            $damager = $source->getDamager();
            if ($damager instanceof Player) {
                $source->setBaseDamage($source->getBaseDamage() / 2);
            }
        }

        parent::attack($source);

        $game = StarPvE::getInstance()->getGameManager()->getGameFromWorld($this->getWorld());
        if ($game instanceof Game) {
            $this->target = $game->getVillager();
        }
    }

    protected function initEntity(CompoundTag $nbt): void {
        parent::initEntity($nbt);

        $game = StarPvE::getInstance()->getGameManager()->getGameFromWorld($this->getWorld());
        if ($game instanceof Game) {
            $this->target = $game->getVillager();
        } else {
            $this->close();
        }
    }

    protected function selectTarget(array $targets): void {
    }

    public function avoidCollidingEntities() {
        #unti!
    }

    protected function getInitialSizeInfo(): EntitySizeInfo {
        return new EntitySizeInfo(1.95, 0.8);
    }

    protected function getInitialFightStyle(): Style {
        return new MeleeStyle($this);
    }

    public function getAddtionalAttackCooldown(): int {
        return 40;
    }

    public function moveForward() {
        if (!$this->isImmobile()) {
            parent::moveForward();
        }
    }

    protected function onTick(int $currentTick, int $tickDiff = 1): void {
        if ($this->getPosition()->distance($this->target->getPosition()) <= 1.2) {
            $this->setImmobile(true);
        } else {
            $this->setImmobile(false);
        }
    }


    public function getFollowRange(): float {
        return 100;
    }

    public function getName(): string {
        return "Attacker";
    }
}
