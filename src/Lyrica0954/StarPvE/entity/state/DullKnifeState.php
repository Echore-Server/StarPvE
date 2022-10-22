<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\entity\state;

use Lyrica0954\StarPvE\entity\EntityState;
use pocketmine\entity\Entity;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\Listener;

class DullKnifeState extends ListenerState {

	const CLAMP_MODIFIER = 19247363;

	public function __construct(Entity $entity, protected float $percentage) {
		parent::__construct($entity);
	}

	/**
	 * @param EntityDamageEvent $event
	 * 
	 * @return void
	 * 
	 * @priority HIGHEST
	 */
	public function onDamage(EntityDamageEvent $event): void {
		$entity = $event->getEntity();
		if ($entity === $this->entity) {
			$amount = $entity->getMaxHealth() * $this->percentage;

			$final = $event->getFinalDamage();
			if ($amount < $final) {
				$over = $final - $amount;
				$event->setModifier(-$over, self::CLAMP_MODIFIER);
			}
		}
	}
}
