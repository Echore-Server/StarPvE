<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\identity\player;

use Lyrica0954\StarPvE\data\condition\Condition;
use Lyrica0954\StarPvE\data\condition\ConditionTrait;
use Lyrica0954\StarPvE\identity\Identity;
use pocketmine\player\Player;

abstract class PlayerArgIdentity extends Identity {
	use ConditionTrait;

	protected ?Player $player;

	public function __construct(?Condition $condition = null) {
		$this->player = null;
		$this->condition = $condition;
	}

	public function setPlayer(?Player $player): void {
		$this->player = $player;
	}

	public function getPlayer(): Player {
		return $this->player;
	}

	public function isApplicable(): bool {
		if ($this->player !== null) {
			$result = $this->condition?->check($this->player) ?? true;
		} else {
			$result = true;
		}

		return $result;
	}

	public function isApplicableFor(Player $player): bool {
		return $this->condition?->check($player) ?? true;
	}
}
