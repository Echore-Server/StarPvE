<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\event\cooltime;

use Lyrica0954\StarPvE\job\cooltime\CooltimeHandler;

class CooltimeFinishEvent extends CooltimeEvent {

	public function __construct(CooltimeHandler $handler){
		$this->handler = $handler;
	}
}