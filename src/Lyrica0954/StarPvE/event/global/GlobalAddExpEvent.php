<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\event\global;

use Lyrica0954\StarPvE\data\adapter\SimpleConfigAdapter;
use pocketmine\event\Cancellable;
use pocketmine\event\CancellableTrait;

class GlobalAddExpEvent extends GlobalEvent implements Cancellable{
	use CancellableTrait;

	/**
	 * @var float
	 */
	protected float $amount;

	public function __construct(SimpleConfigAdapter $adapter, float $amount){
		$this->adapter = $adapter;
		$this->amount = $amount;
	}

	public function getAmount(): float{
		return $this->amount;
	}
}