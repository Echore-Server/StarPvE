<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\task;

use Lyrica0954\StarPvE\job\cooltime\CooltimeHandler;
use Lyrica0954\StarPvE\StarPvE;

trait CooltimeHolder {

	protected ?CooltimeHandler $cooltimeHandler = null;

	protected function createCooltimeHandler(string $id, int $baseTick, int $speed = 1) {
		if ($this->cooltimeHandler !== null) {
			$this->breakCooltimeHandler();
			StarPvE::getInstance()->log("§7[CooltimeHolder] Task Safety: Broke Current CooltimeHandler");
		}
		$this->cooltimeHandler = new CooltimeHandler($id, $baseTick, $speed);
		$this->cooltimeHandler->attach($this);
	}

	protected function breakCooltimeHandler() {
		$this->cooltimeHandler?->detach();
		$this->cooltimeHandler = null;
	}
}
