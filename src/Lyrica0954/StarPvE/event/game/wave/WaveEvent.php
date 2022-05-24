<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\event\game\wave;

use Lyrica0954\StarPvE\event\game\GameEvent;
use Lyrica0954\StarPvE\game\Game;

class WaveEvent extends GameEvent {

	/**
	 * @var int
	 */
	protected int $wave;

	public function getWave(): int {
		return $this->wave;
	}

	public function __construct(Game $game) {
		$this->game = $game;
	}
}
