<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\event\data\inventory;

use Lyrica0954\StarPvE\data\inventory\VirtualInventory;
use pocketmine\event\Event;

class VirtualInventoryEvent extends Event {

	/**
	 * @var VirtualInventory
	 */
	protected VirtualInventory $inventory;

	public function getInventory(): VirtualInventory {
		return $this->inventory;
	}
}
