<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\game\monster\history;

use pocketmine\entity\Entity;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;

class EntityDamageHistory {

	/**
	 * @var float[]
	 */
	protected array $history;

	protected Entity $entity;

	public function __construct(Entity $entity) {
		$this->history = [];
		$this->entity = $entity;
	}

	/**
	 * @return float[]
	 */
	public function getHistory(): array {
		return $this->history;
	}

	public function getEntity(): Entity {
		return $this->entity;
	}

	public function add(Entity $damager, float $finalDamage): void {
		$hash = spl_object_hash($damager);
		$this->history[$hash] ?? ($this->history[$hash] = 0);
		$this->history[$hash] += $finalDamage;
	}

	public function handle(EntityDamageEvent $event): void {
		$entity = $event->getEntity();

		if ($event instanceof EntityDamageByEntityEvent) {
			$damager = $event->getDamager();

			if ($entity === $this->entity) {
				$this->add($damager, $event->getFinalDamage());
			}
		}
	}
}
