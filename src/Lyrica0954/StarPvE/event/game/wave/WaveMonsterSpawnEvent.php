<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\event\game\wave;

use Lyrica0954\StarPvE\game\Game;
use Lyrica0954\StarPvE\game\wave\MonsterAttribute;
use Lyrica0954\StarPvE\game\wave\MonsterOption;
use Lyrica0954\StarPvE\game\wave\WaveMonsters;
use pocketmine\world\Position;

class WaveMonsterSpawnEvent extends WaveEvent {

	protected WaveMonsters $waveMonsters;

	protected Position $position;

	/**
	 * @var MonsterOption[]
	 */
	protected array $options;

	/**
	 * @param Game $game
	 * @param int $wave
	 * @param WaveMonsters $waveMonsters
	 * @param Position $position
	 * @param MonsterOption[] $option
	 */
	public function __construct(Game $game, int $wave, WaveMonsters $waveMonsters, Position $position, array $options) {
		parent::__construct($game);
		$this->wave = $wave;
		$this->waveMonsters = $waveMonsters;
		$this->position = $position;
		$this->options = $options;
	}

	public function getWaveMonsters(): WaveMonsters {
		return $this->waveMonsters;
	}

	public function getPosition(): Position {
		return $this->position;
	}

	/**
	 * @return MonsterOption[]
	 */
	public function getOptions(): array {
		return $this->options;
	}

	public function setPosition(Position $position): void {
		$this->position = $position;
	}
}
