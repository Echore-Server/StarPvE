<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\event\global;

use Lyrica0954\StarPvE\data\adapter\SimpleConfigAdapter;
use pocketmine\event\Cancellable;
use pocketmine\event\CancellableTrait;
use pocketmine\event\player\PlayerEvent;
use pocketmine\player\Player;

class GlobalLevelupEvent extends GlobalEvent{

	protected int $old;
	protected int $new;

	public function __construct(SimpleConfigAdapter $adapter, int $old, int $new){
		$this->adapter = $adapter;
		$this->old = $old;
		$this->new = $new;
	}

	public function getOld(): int{
		return $this->old;
	}

	public function getNew(): int{
		return $this->new;
	}
}