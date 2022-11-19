<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\entity\state;

use Lyrica0954\StarPvE\entity\DamageCause;
use Lyrica0954\StarPvE\entity\EntityState;
use Lyrica0954\StarPvE\utils\EntityUtil;
use pocketmine\entity\Entity;
use pocketmine\event\entity\EntityDamageEvent;

class FrozenState extends ListenerState {

	public function __construct(Entity $entity, protected float $multiplier) {
		parent::__construct($entity);
	}

	public function onDamage(EntityDamageEvent $event): void {
		if ($event->getEntity() !== $this->entity) {
			return;
		}

		EntityUtil::immobile($this->entity, 3);

		if ($event->getCause() === DamageCause::CAUSE_ELECTRIFICATION) {
			EntityUtil::multiplyFinalDamage($event, $this->multiplier);
		}
	}
}
