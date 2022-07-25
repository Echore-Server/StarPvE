<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\event\job\player;

use Lyrica0954\StarPvE\job\player\PlayerJob;
use pocketmine\player\Player;

class PlayerSelectJobEvent extends PlayerJobEvent {


	public function __construct(Player $player, PlayerJob $job) {
		$this->player = $player;
		$this->job = $job;
	}
}
