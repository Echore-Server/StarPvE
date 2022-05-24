<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\game\identity;

use Lyrica0954\StarPvE\game\Game;
use Lyrica0954\StarPvE\identity\Identity;

abstract class GameArgIdentity extends Identity {

	protected ?Game $game;

	public function __construct() {
		parent::__construct();

		$this->game = null;
	}

	public function getGame(): ?Game {
		return $this->game;
	}

	public function setGame(Game $game): void {
		$this->game = $game;
	}

	public function isApplicable(): bool {
		return true;
	}
}
