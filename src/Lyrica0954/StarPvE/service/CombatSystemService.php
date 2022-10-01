<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\service;

use pocketmine\entity\Living;
use pocketmine\event\entity\EntityDamageEvent;

class CombatSystemService extends ListenerService {

	/**
	 * @param EntityDamageEvent $event
	 * 
	 * @return void
	 * 
	 * @priority LOWEST
	 */
	public function onDamage(EntityDamageEvent $event): void {
		$entity = $event->getEntity();
		if ($entity instanceof Living) {
			if ($event->getCause() === EntityDamageEvent::CAUSE_MAGIC) {
				$event->setModifier($event->getFinalDamage() * $entity->getArmorPoints() * 0.04, EntityDamageEvent::MODIFIER_ARMOR);
				$event->setModifier(-$event->getModifier(EntityDamageEvent::MODIFIER_ARMOR_ENCHANTMENTS), EntityDamageEvent::MODIFIER_ARMOR_ENCHANTMENTS);
			}
		}
	}
}
