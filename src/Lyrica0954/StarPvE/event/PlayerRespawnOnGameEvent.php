<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\event;

use pocketmine\event\Cancellable;
use pocketmine\event\CancellableTrait;
use pocketmine\event\player\PlayerEvent;
use pocketmine\player\Player;

class PlayerRespawnOnGameEvent extends PlayerEvent {
	public function __construct(Player $player) {
		$this->player = $player;
	}
}
