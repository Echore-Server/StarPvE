<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\game\identity;

use Lyrica0954\StarPvE\event\game\wave\WaveMonsterSpawnEvent;
use Lyrica0954\StarPvE\game\Game;
use Lyrica0954\StarPvE\game\wave\WaveMonsters;
use pocketmine\event\HandlerListManager;
use pocketmine\event\Listener;

abstract class ModifyMonsterArgIdentityBase extends GameArgIdentity implements Listener {

	protected bool $applied;

	public function __construct() {
		parent::__construct();

		$this->applied = false;
	}

	public function onWaveMonsterSpawn(WaveMonsterSpawnEvent $event) {
		$game = $event->getGame();

		if ($game === $this->game) {
			$this->onSpawn($event);
		}
	}

	abstract protected function onSpawn(WaveMonsterSpawnEvent $event): void;

	public function apply(): void {
		$this->registerEvent();
		$this->applied = true;
	}

	public function reset(): void {
		if ($this instanceof Listener) HandlerListManager::global()->unregisterAll($this);
		$this->applied = false;
	}
}
