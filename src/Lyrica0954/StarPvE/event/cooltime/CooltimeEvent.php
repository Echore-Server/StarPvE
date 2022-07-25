<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\event\cooltime;

use Lyrica0954\StarPvE\job\cooltime\CooltimeHandler;
use pocketmine\event\Event;

class CooltimeEvent extends Event {

	/**
	 * @var CooltimeHandler
	 */
	protected CooltimeHandler $handler;

	/**
	 * @return CooltimeHandler
	 */
	public function getHandler(): CooltimeHandler {
		return $this->handler;
	}
}
