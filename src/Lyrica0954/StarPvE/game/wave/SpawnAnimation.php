<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\game\wave;

use Lyrica0954\StarPvE\utils\TaskUtil;
use pocketmine\entity\Entity;
use pocketmine\entity\Living;

class SpawnAnimation {

	private \Closure $animator;
	private int $period;

	protected ?\Closure $initiator;

	public function __construct(\Closure $animator, int $animatorPeriod) {
		$this->animator = $animator;
		$this->period = $animatorPeriod;
		$this->initiator = null;
	}

	public function getAnimator(): \Closure {
		return $this->animator;
	}

	public function getInitiator(): ?\Closure {
		return $this->initiator;
	}

	public function setInitiator(\Closure $initiator): void {
		$this->initiator = $initiator;
	}

	public function spawn(Living $living): void {
		if ($this->initiator !== null) {
			($this->initiator)($living);
		}

		$living->spawnToAll();


		TaskUtil::repeatingClosureCheck(function () {
		}, $this->period, function () use ($living) {
			return (($this->animator)($living));
		});
	}
}
