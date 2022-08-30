<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\player\rank;

use pocketmine\player\Player;

class Rank {

	protected string $prefix;

	public function __construct(string $prefix = "") {
		$this->prefix = $prefix;
	}

	public function getPlayerName(Player $player): string {
		return $this->prefix . $player->getName();
	}

	public function getPrefix(): string {
		return $this->prefix;
	}
}
