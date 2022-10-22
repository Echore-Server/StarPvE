<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\entity\state;

use Lyrica0954\StarPvE\utils\EntityUtil;
use pocketmine\entity\Entity;
use pocketmine\event\entity\EntityDamageEvent;

class MagicResistanceState extends ListenerState {

	public function __construct(Entity $entity, protected float $percentage) {
		parent::__construct($entity);
	}

	/**
	 * @param EntityDamageEvent $event
	 * 
	 * @return void
	 * 
	 * @priority HIGH
	 */
	public function onDamage(EntityDamageEvent $event): void {
		$entity = $event->getEntity();
		if ($entity === $this->entity && $event->getCause() === EntityDamageEvent::CAUSE_MAGIC) {
			EntityUtil::multiplyFinalDamage($event, $this->percentage);
		}
	}
}
