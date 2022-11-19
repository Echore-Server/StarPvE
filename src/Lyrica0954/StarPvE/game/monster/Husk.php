<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\game\monster;

use Lyrica0954\SmartEntity\entity\walking\Husk as SmartHusk;
use Lyrica0954\SmartEntity\utils\VectorUtil;
use Lyrica0954\StarPvE\entity\MotionResistance;
use Lyrica0954\StarPvE\utils\EntityUtil;
use Lyrica0954\StarPvE\utils\HealthBarEntity;
use pocketmine\entity\animation\ArmSwingAnimation;
use pocketmine\entity\Entity;
use pocketmine\event\entity\EntityDamageByEntityEvent;

class Husk extends SmartHusk implements MotionResistance {
	use HealthBarEntity;

	protected float $reach = 2.0;

	public function getMotionResistance(): float {
		return 0.65;
	}

	public function attackEntity(Entity $entity): bool {
		$range = VectorUtil::distanceToAABB($this->getEyePos(), $entity->getBoundingBox());
		if ($this->isAlive() && $range <= $this->getAttackRange() && $this->attackCooldown <= 0) {
			$this->broadcastAnimation(new ArmSwingAnimation($this));
			$source = new EntityDamageByEntityEvent($this, $entity, EntityDamageByEntityEvent::CAUSE_ENTITY_ATTACK, $this->getAttackDamage());
			EntityUtil::attackEntity($source, 1.0, 1.6);
			$this->attackCooldown = $source->getAttackCooldown() + $this->getAddtionalAttackCooldown();

			$this->hitEntity($entity, $range);
			return true;
		} else {
			return false;
		}
	}

	public function getFollowRange(): float {
		return 50;
	}
}
