<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\event\game\wave;

use Lyrica0954\StarPvE\game\Game;

class WaveStartEvent extends WaveEvent {

	public function __construct(Game $game, int $wave) {
		parent::__construct($game);
		$this->wave = $wave;
	}
}
