<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\game\identity;

use Lyrica0954\StarPvE\game\Game;
use Lyrica0954\StarPvE\identity\Identity;

abstract class GameIdentity extends Identity {

	protected Game $game;

	public function __construct(Game $game) {
		parent::__construct();

		$this->game = $game;
	}

	public function getGame(): Game {
		return $this->game;
	}

	public function isApplicable(): bool {
		return true;
	}
}
