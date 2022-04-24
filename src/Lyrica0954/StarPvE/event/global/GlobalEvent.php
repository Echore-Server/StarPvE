<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\event\global;

use Lyrica0954\StarPvE\data\adapter\SimpleConfigAdapter;
use pocketmine\event\Event;

class GlobalEvent extends Event{

	/**
	 * @var SimpleConfigAdapter
	 */
	protected SimpleConfigAdapter $adapter;

	/**
	 * @return SimpleConfigAdapter
	 */
	public function getAdapter(): SimpleConfigAdapter{
		return $this->adapter;
	}
}