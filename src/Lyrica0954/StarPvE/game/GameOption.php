<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\game;

class GameOption {

	protected int $maxPlayers;

	public static function manual(int $maxPlayers = 6): self {
		return new self($maxPlayers);
	}

	public function __construct(int $maxPlayers) {
		$this->maxPlayers = $maxPlayers;
	}

	public function getMaxPlayers(): int {
		return $this->maxPlayers;
	}

	public function setMaxPlayers(int $maxPlayers): void {
		$this->maxPlayers = $maxPlayers;
	}
}
