<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\game\monster\fightstyle;

use Lyrica0954\MagicParticle\effect\LightningEffect;
use Lyrica0954\MagicParticle\ParticleOption;
use Lyrica0954\SmartEntity\entity\fightstyle\Style;
use Lyrica0954\SmartEntity\entity\LivingBase;
use Lyrica0954\SmartEntity\entity\walking\FightingEntity;
use Lyrica0954\SmartEntity\utils\ProjectileHelper;
use Lyrica0954\StarPvE\utils\ParticleUtil;
use Lyrica0954\StarPvE\utils\PlayerUtil;
use pocketmine\entity\Entity;
use pocketmine\entity\projectile\ProjectileSource;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\network\mcpe\protocol\AnimateEntityPacket;
use pocketmine\network\mcpe\protocol\types\ActorEvent;
use pocketmine\world\Position;

class MagicRangedStyle extends Style {

	public function getTarget(): ?Entity {
		return $this->entity->getTarget();
	}

	public function onTick(int $currentTick, int $tickDiff = 1): void {
		$entity = $this->entity;
		if ($entity->getTarget() !== null) {
			$aiming = true;

			if ($entity->isInAttackRange($this->getTarget())) {
				if ($entity->getPosition()->distance($this->getTarget()->getPosition()) <= 5.0) {
					$entity->moveBackwards(0.2);
				}

				if ($entity->getAttackCooldown() <= 0) {
					$eyePos = $entity->getEyePos();
					$target = $this->getTarget();
					$par = new LightningEffect(Position::fromObject($eyePos, $entity->getWorld()), 0.5, 0.5);
					PlayerUtil::broadcastSound($entity, "ambient.weather.thunder", 1.5, 0.4);
					PlayerUtil::broadcastSound($entity, "ambient.weather.lightning.impact", 1.3, 0.25);
					ParticleUtil::send(
						$par,
						$entity->getWorld()->getPlayers(),
						Position::fromObject($target->getEyePos(), $target->getWorld()),
						ParticleOption::spawnPacket("starpve:lightning_sparkler")
					);
					$source = new EntityDamageByEntityEvent($entity, $target, EntityDamageEvent::CAUSE_MAGIC, $entity->getAttackDamage(), [], 0.0);
					$source->setAttackCooldown(0);
					$target->attack($source);

					$pk = AnimateEntityPacket::create("animation.evoker.casting", "", "query.anim_time >= 2.0;", 1, "", 1, [$entity->getId()]);
					foreach ($entity->getWorld()->getPlayers() as $player) {
						$player->getNetworkSession()->sendDataPacket($pk);
					}

					$entity->setAttackCooldown($entity->getAddtionalAttackCooldown());
				}
			} else {
				$entity->moveForward();
			}

			if ($currentTick % $entity->getAimFlex() == 0 && $aiming) {
				$entity->lookAt($this->getTarget()->getPosition());
			}
		}
	}
}
