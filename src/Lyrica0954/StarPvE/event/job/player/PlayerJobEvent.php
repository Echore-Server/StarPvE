<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\event\job\player;

use Lyrica0954\StarPvE\job\Job;
use Lyrica0954\StarPvE\job\player\PlayerJob;
use pocketmine\event\Event;
use pocketmine\event\player\PlayerEvent;

class PlayerJobEvent extends PlayerEvent{

	protected PlayerJob $job;

	public function getJob(): PlayerJob{
		return $this->job;
	}
}