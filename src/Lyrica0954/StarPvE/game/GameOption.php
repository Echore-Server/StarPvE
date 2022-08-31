<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\game;

class GameOption {

	protected int $maxPlayers;

	protected int $minPlayers;

	protected float $xpMultiplier;

	public static function manual(int $maxPlayers = 6, int $minPlayers = 1, float $xpMultiplier = 1.0): self {
		return new self($maxPlayers, $minPlayers, $xpMultiplier);
	}

	public function __construct(int $maxPlayers, int $minPlayers, float $xpMultiplier) {
		$this->maxPlayers = $maxPlayers;
		$this->minPlayers = $minPlayers;
		$this->xpMultiplier = $xpMultiplier;
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

	public function getXpMultiplier(): float {
		return $this->xpMultiplier;
	}

	public function setXpMultiplier(float $xpMultiplier): void {
		$this->xpMultiplier = $xpMultiplier;
	}
}
