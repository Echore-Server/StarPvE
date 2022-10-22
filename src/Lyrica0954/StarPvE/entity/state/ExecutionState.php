<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\entity\state;

use pocketmine\entity\Entity;
use pocketmine\event\entity\EntityDamageByEntityEvent;

class ExecutionState extends ListenerState {

	public function __construct(Entity $entity, protected float $percentage) {
		parent::__construct($entity);
	}

	public function onEntityDamageByEntity(EntityDamageByEntityEvent $event) {
		$damager = $event->getDamager();
		$entity = $event->getEntity();

		if ($damager === $this->entity) {
			if ($entity->getHealth() <= ($entity->getMaxHealth() * $this->percentage)) {
				$entity->kill();
			}
		}
	}
}
