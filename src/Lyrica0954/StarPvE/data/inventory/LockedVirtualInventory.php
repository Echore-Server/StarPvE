<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\data\inventory;

use pocketmine\inventory\Inventory;
use pocketmine\inventory\PlayerCursorInventory;
use pocketmine\inventory\transaction\action\InventoryAction;
use pocketmine\item\Item;

class LockedVirtualInventory extends VirtualInventory {

	protected function onDropAction(Item $item): bool {
		return false;
	}

	protected function onTransfer(int $fromSlot, int $toSlot, Item $fromItem, Item $toItem, Inventory $from, Inventory $to): bool {
		print_r("From: " . $from::class . "\n");
		print_r("To: " . $to::class . "\n");
		if (($to instanceof PlayerCursorInventory) && $from == $this) {
			return true;
		}

		if ($from instanceof PlayerCursorInventory && $to == $this) {
			return true;
		}

		return false;
	}

	protected function onRawAction(InventoryAction $action): bool {
		return true;
	}
}
