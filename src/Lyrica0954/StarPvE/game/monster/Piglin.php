<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\game\monster;

use Lyrica0954\SmartEntity\entity\fightstyle\MeleeStyle;
use Lyrica0954\SmartEntity\entity\fightstyle\RangedStyle;
use Lyrica0954\SmartEntity\entity\fightstyle\Style;
use Lyrica0954\SmartEntity\entity\Hostile;
use Lyrica0954\SmartEntity\entity\walking\FightingEntity;
use Lyrica0954\SmartEntity\entity\walking\Zombie as SmartZombie;
use Lyrica0954\StarPvE\utils\EntityUtil;
use Lyrica0954\StarPvE\utils\HealthBarEntity;
use Lyrica0954\StarPvE\utils\PlayerUtil;
use pocketmine\entity\animation\ArmSwingAnimation;
use pocketmine\entity\Entity;
use pocketmine\entity\EntitySizeInfo;
use pocketmine\entity\projectile\ProjectileSource;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;
use pocketmine\player\Player;

class Piglin extends FightingEntity implements Hostile, ProjectileSource {
    use HealthBarEntity;

    public static function getNetworkTypeId(): string {
        return EntityIds::PIGLIN;
    }

    protected float $reach = 1.5;

    public function getFollowRange(): float {
        return 50;
    }

    public function getName(): string {
        return "Piglin";
    }

    protected function getInitialSizeInfo(): EntitySizeInfo {
        return new EntitySizeInfo(1.8, 0.6);
    }

    protected function getInitialFightStyle(): Style {
        return new MeleeStyle($this);
    }

    public function getAddtionalAttackCooldown(): int {
        return 14;
    }

    protected function onTick(int $currentTick, int $tickDiff = 1): void {
    }

    public function hitEntity(Entity $entity, float $range): void {
        if ($entity instanceof Player) {
            PlayerUtil::playSound($entity, "random.break", 0.5, 0.75);
        }
    }

    public function attackEntity(Entity $entity, float $range): bool {
        if ($this->isAlive() && $range <= $this->getAttackRange() && $this->attackCooldown <= 0) {
            $this->broadcastAnimation(new ArmSwingAnimation($this));
            $source = new EntityDamageByEntityEvent($this, $entity, EntityDamageByEntityEvent::CAUSE_ENTITY_ATTACK, $this->getAttackDamage());
            $kb = EntityUtil::attackEntity($source, 2.8, 1.0);

            if ($kb->lengthSquared() > 0) {
                EntityUtil::immobile($this, 10);
                $this->hitEntity($entity, $range);
            }
            $this->attackCooldown = $source->getAttackCooldown() + $this->getAddtionalAttackCooldown();
            return true;
        } else {
            return false;
        }
    }
}
