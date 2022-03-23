<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\event\game;

use Lyrica0954\StarPvE\game\Game;

class GameStartEvent extends GameEvent {

	public function __construct(Game $game){
		$this->game = $game;
	}
}