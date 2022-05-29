<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\job\skill;

use Lyrica0954\StarPvE\job\Ability;
use Lyrica0954\StarPvE\job\ActionResult;
use Lyrica0954\StarPvE\job\Skill;
use pocketmine\entity\Entity;

abstract class ThrowEntitySkillBase extends Skill {

	/**
	 * @var Entity[]
	 */
	protected array $entities = [];

	protected function onActivate(): ActionResult {
		$entity = $this->getEntity();
		$motion = $this->player->getDirectionVector()->multiply($this->speed->get());
		$entity->setMotion($motion);
		$entity->setOwningEntity($this->player);
		$entity->spawnToAll();

		$this->entities[] = $entity;

		return ActionResult::SUCCEEDED();
	}

	public function close(): void {

		foreach ($this->entities as $entity) {
			if (!$entity->isClosed()) {
				$entity->close();
			}
		}

		$this->entities = [];

		parent::close();
	}

	abstract protected function getEntity(): Entity;
}
