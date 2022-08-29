<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\game;

class GameOption {

	protected int $maxPlayers;

	protected int $minPlayers;

	public static function manual(int $maxPlayers = 6, int $minPlayers = 1): self {
		return new self($maxPlayers, $minPlayers);
	}

	public function __construct(int $maxPlayers, int $minPlayers) {
		$this->maxPlayers = $maxPlayers;
		$this->minPlayers = $minPlayers;
	}

	public function getMaxPlayers(): int {
		return $this->maxPlayers;
	}

	public function setMaxPlayers(int $maxPlayers): void {
		$this->maxPlayers = $maxPlayers;
	}

	public function getMinPlayers(): int {
		return $this->minPlayers;
	}

	public function setMinPlayers(int $minPlayers) {
		$this->minPlayers = $minPlayers;
	}
}
