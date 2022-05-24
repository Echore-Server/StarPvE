<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\event\game\wave;

use Lyrica0954\StarPvE\game\Game;
use Lyrica0954\StarPvE\game\wave\MonsterAttribute;
use Lyrica0954\StarPvE\game\wave\WaveMonsters;
use pocketmine\world\Position;

class WaveMonsterSpawnEvent extends WaveEvent {

	protected WaveMonsters $waveMonsters;

	protected Position $position;

	public function __construct(Game $game, int $wave, WaveMonsters $waveMonsters, Position $position, array $attributes) {
		parent::__construct($game);
		$this->wave = $wave;
		$this->waveMonsters = $waveMonsters;
		$this->position = $position;
		$this->attributes = $attributes;
	}

	public function getWaveMonsters(): WaveMonsters {
		return $this->waveMonsters;
	}

	public function getPosition(): Position {
		return $this->position;
	}

	/**
	 * @return MonsterAttribute[]
	 */
	public function getAttributes(): array {
		return $this->attributes;
	}

	public function setPosition(Position $position): void {
		$this->position = $position;
	}
}
