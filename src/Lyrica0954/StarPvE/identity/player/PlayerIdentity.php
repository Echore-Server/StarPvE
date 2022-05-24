<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\identity\player;

use Lyrica0954\StarPvE\data\condition\Condition;
use Lyrica0954\StarPvE\data\condition\ConditionTrait;
use Lyrica0954\StarPvE\identity\Identity;
use pocketmine\player\Player;

abstract class PlayerIdentity extends Identity {
	use ConditionTrait;

	protected Player $player;

	public function __construct(Player $player, ?Condition $condition = null) {
		$this->player = $player;
		$this->condition = $condition;
	}

	public function getPlayer(): Player {
		return $this->player;
	}

	public function isApplicable(): bool {
		$result = $this->condition?->check($this->player) ?? true;

		return $result;
	}
}
