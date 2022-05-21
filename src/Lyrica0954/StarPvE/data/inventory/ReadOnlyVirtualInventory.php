<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\data\inventory;

use pocketmine\inventory\Inventory;
use pocketmine\inventory\transaction\action\InventoryAction;
use pocketmine\item\Item;

class ReadOnlyVirtualInventory extends VirtualInventory {

	protected function onDropAction(Item $item): bool {
		return false;
	}

	protected function onTransfer(int $fromSlot, int $toSlot, Item $fromItem, Item $toItem, Inventory $from, Inventory $to): bool {
		return false;
	}

	protected function onRawAction(InventoryAction $action): bool {
		return false;
	}
}
