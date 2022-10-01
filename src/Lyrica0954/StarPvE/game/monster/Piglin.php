<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\game\monster;

use Lyrica0954\SmartEntity\entity\fightstyle\MeleeStyle;
use Lyrica0954\SmartEntity\entity\fightstyle\RangedStyle;
use Lyrica0954\SmartEntity\entity\fightstyle\Style;
use Lyrica0954\SmartEntity\entity\Hostile;
use Lyrica0954\SmartEntity\entity\walking\FightingEntity;
use Lyrica0954\SmartEntity\entity\walking\Zombie as SmartZombie;
use Lyrica0954\SmartEntity\utils\VectorUtil;
use Lyrica0954\StarPvE\utils\EntityUtil;
use Lyrica0954\StarPvE\utils\HealthBarEntity;
use Lyrica0954\StarPvE\utils\PlayerUtil;
use pocketmine\entity\animation\ArmSwingAnimation;
use pocketmine\entity\Entity;
use pocketmine\entity\EntitySizeInfo;
use pocketmine\entity\Location;
use pocketmine\entity\projectile\Arrow;
use pocketmine\entity\projectile\ProjectileSource;
use pocketmine\event\entity\EntityDamageByChildEntityEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataFlags;
use pocketmine\player\Player;

class Piglin extends FightingEntity implements Hostile, ProjectileSource {
	use HealthBarEntity;

	public static function getNetworkTypeId(): string {
		return EntityIds::PIGLIN;
	}

	protected float $reach = 1.2;

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

	public function attack(EntityDamageEvent $source): void {
		if ($source instanceof EntityDamageByChildEntityEvent) {
			$child = $source->getChild();
			$damager = $source->getDamager();
			if ($damager instanceof Player) {
				$vec = $this->getEyePos();
				$loc = $this->getLocation();
				$loc->y = $vec->y;

				$tloc = $damager->getLocation();
				$tloc->y += $damager->getEyeHeight();

				$d = $tloc->subtractVector($loc)->normalize();
				$projectile = new Arrow($loc, $this, true);
				$projectile->setMotion($d->multiply(5));
				$projectile->spawnToAll();
				$source->setBaseDamage($source->getBaseDamage() / 2);
			}
		}

		parent::attack($source);
	}

	protected function onTick(int $currentTick, int $tickDiff = 1): void {
	}

	public function hitEntity(Entity $entity, float $range): void {
		if ($entity instanceof Player) {
			PlayerUtil::playSound($entity, "random.break", 0.5, 0.75);
		}
	}

	public function attackEntity(Entity $entity): bool {
		$dist = VectorUtil::distanceToAABB($this->getEyePos(), $entity->getBoundingBox());
		if ($this->isAlive() && $dist <= $this->getAttackRange() && $this->attackCooldown <= 0) {
			$this->broadcastAnimation(new ArmSwingAnimation($this));
			$source = new EntityDamageByEntityEvent($this, $entity, EntityDamageByEntityEvent::CAUSE_ENTITY_ATTACK, $this->getAttackDamage());
			$kb = EntityUtil::attackEntity($source, 2.8, 1.0);

			if ($kb->lengthSquared() > 0) {
				EntityUtil::immobile($this, 10);
				$this->hitEntity($entity, $dist);
			}
			$this->attackCooldown = $source->getAttackCooldown() + $this->getAddtionalAttackCooldown();
			return true;
		} else {
			return false;
		}
	}
}
