<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\event\game;

use Lyrica0954\StarPvE\game\Game;
use pocketmine\event\Event;
use pocketmine\event\player\PlayerEvent;

class GameEvent extends Event {

	/**
	 * @var Game
	 */
	protected Game $game;

	public function getGame(): Game {
		return $this->game;
	}
}
