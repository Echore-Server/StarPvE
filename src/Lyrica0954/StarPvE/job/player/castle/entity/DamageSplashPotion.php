<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\job\player\castle\entity;

use Lyrica0954\StarPvE\game\wave\MonsterData;
use pocketmine\entity\projectile\SplashPotion;
use pocketmine\event\entity\ProjectileHitEvent;
use pocketmine\color\Color;
use pocketmine\entity\effect\InstantEffect;
use pocketmine\entity\Entity;
use pocketmine\entity\Living;
use pocketmine\entity\Location;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\ProjectileHitEntityEvent;
use pocketmine\item\PotionType;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\world\particle\PotionSplashParticle;
use pocketmine\world\sound\PotionSplashSound;

class DamageSplashPotion extends SplashPotion {

	public float $areaDamage = 0.0;

	public function __construct(Location $location, ?Entity $shootingEntity, ?CompoundTag $nbt = null) {
		parent::__construct($location, $shootingEntity, PotionType::HARMING(), null);
	}

	protected function onHit(ProjectileHitEvent $event): void {
		$effects = $this->getPotionEffects();
		$hasEffects = true;

		if (count($effects) === 0) {
			$particle = new PotionSplashParticle(PotionSplashParticle::DEFAULT_COLOR());
			$hasEffects = false;
		} else {
			$colors = [];
			foreach ($effects as $effect) {
				$level = $effect->getEffectLevel();
				for ($j = 0; $j < $level; ++$j) {
					$colors[] = $effect->getColor();
				}
			}
			$particle = new PotionSplashParticle(Color::mix(...$colors));
		}

		$this->getWorld()->addParticle($this->location, $particle);
		$this->broadcastSound(new PotionSplashSound());

		if ($hasEffects) {
			if (!$this->willLinger()) {
				foreach ($this->getWorld()->getCollidingEntities($this->boundingBox->expandedCopy(4.125, 2.125, 4.125), $this) as $entity) {
					if (MonsterData::isMonster($entity)) {
						$distanceSquared = $entity->getEyePos()->distanceSquared($this->location);
						if ($distanceSquared > 16) { //4 blocks
							continue;
						}

						$distanceMultiplier = 1 - (sqrt($distanceSquared) / 4);
						if ($event instanceof ProjectileHitEntityEvent && $entity === $event->getEntityHit()) {
							$distanceMultiplier = 1.0;
						}

						$source = new EntityDamageByEntityEvent($this, $entity, EntityDamageEvent::CAUSE_MAGIC, $this->areaDamage, [], 0.0);
						$source->setAttackCooldown(0);
						$entity->attack($source);
					}
				}
			} else {
				//TODO: lingering potions
			}
		}
	}
}
