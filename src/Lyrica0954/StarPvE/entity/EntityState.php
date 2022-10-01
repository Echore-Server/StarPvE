<?php


declare(strict_types=1);

namespace Lyrica0954\StarPvE\entity;

use pocketmine\entity\Entity;

abstract class EntityState {

	protected Entity $entity;

	public function __construct(Entity $entity) {
		$this->entity = $entity;
	}

	abstract public function start(): void;

	public function close(): void {
	}

	public function getEntity(): Entity {
		return $this->entity;
	}
}
