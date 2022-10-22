<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\identity\player;

use Lyrica0954\StarPvE\data\condition\Condition;
use Lyrica0954\StarPvE\entity\EntityState;
use Lyrica0954\StarPvE\entity\EntityStateManager;
use pocketmine\player\Player;

class AddStateIdentity extends PlayerIdentity {

	protected EntityState $state;

	private int $id;

	public function __construct(?Condition $condition = null, EntityState $state, protected string $description) {
		$entity = $state->getEntity();
		assert($entity instanceof Player);
		parent::__construct($entity, $condition);
		$this->state = $state;
		$this->id = -1;
	}

	public function apply(): void {
		$id = EntityStateManager::nextStateId();
		$this->id = $id;
		EntityStateManager::start($this->state, $id);
	}

	public function reset(): void {
		if ($this->id !== -1) {
			EntityStateManager::end($this->state->getEntity()->getId(), $this->id);
		}
	}

	public function getName(): string {
		return "状態追加";
	}

	/**
	 * @return string
	 */
	public function getDescription(): string {
		return $this->description;
	}

	/**
	 * Get the value of state
	 *
	 * @return EntityState
	 */
	public function getState(): EntityState {
		return $this->state;
	}
}
