<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\job;

use Lyrica0954\StarPvE\job\cooltime\CooltimeHandler;
use Lyrica0954\StarPvE\job\player\PlayerJob;
use pocketmine\item\Item;

abstract class Spell extends Ability {

	public function __construct(PlayerJob $job) {
		parent::__construct($job);
		$this->cooltimeHandler = new CooltimeHandler($this->getName(), CooltimeHandler::BASE_TICK, 1);
	}

	abstract public function getActivateItem(): Item;
}
