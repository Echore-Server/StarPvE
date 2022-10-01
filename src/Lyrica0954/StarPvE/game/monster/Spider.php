<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\game\monster;

use Lyrica0954\MagicParticle\ParticleOption;
use Lyrica0954\MagicParticle\SingleParticle;
use Lyrica0954\SmartEntity\entity\walking\Spider as SmartSpider;
use Lyrica0954\StarPvE\utils\EntityUtil;
use Lyrica0954\StarPvE\utils\HealthBarEntity;
use Lyrica0954\StarPvE\utils\ParticleUtil;
use Lyrica0954\StarPvE\utils\PlayerUtil;
use pocketmine\entity\animation\ArmSwingAnimation;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\entity\Entity;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\player\Player;
use pocketmine\world\particle\ExplodeParticle;

class Spider extends SmartSpider {
	use HealthBarEntity;

	protected float $reach = 1.2;

	protected function onTick(int $currentTick, int $tickDiff = 1): void {
		if ($currentTick % 70 == 0) {
			foreach (EntityUtil::getWithinRange($this->getPosition(), $this->getAttackRange() * 2.0) as $entity) {
				if ($entity instanceof Player) {
					if (!$entity->isSpectator() && $entity->isAlive()) {
						PlayerUtil::playSound($entity, "mob.spider.death", 7.0, 0.6);

						$ef = $entity->getEffects();
						$ef->add(new EffectInstance(VanillaEffects::SLOWNESS(), 4 * 20, 2, false));
						$ef->add(new EffectInstance(VanillaEffects::BLINDNESS(), 1 * 20, 0, false));
						$ef->add(new EffectInstance(VanillaEffects::POISON(), 2 * 20, 2, false));
						$ef->add(new EffectInstance(VanillaEffects::WEAKNESS(), 1 * 20, 0, false));

						$par = new SingleParticle();
						$epos = $entity->getPosition();
						$epos->y += $entity->getEyeHeight();
						ParticleUtil::send($par, $this->getWorld()->getPlayers(), $epos, ParticleOption::levelEvent(new ExplodeParticle));
					}
				}
			}
		}
	}

	public function getFollowRange(): float {
		return 50;
	}
}
