<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\event\job;

use Lyrica0954\StarPvE\job\Job;
use Lyrica0954\StarPvE\job\player\PlayerJob;
use pocketmine\event\Event;

class JobEvent extends Event {

	protected Job $job;

	public function getJob(): Job {
		return $this->job;
	}
}
