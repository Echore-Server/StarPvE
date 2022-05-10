<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\data\inventory;

use pocketmine\item\Item;

class ReadOnlyVirtualInventory extends VirtualInventory {

	protected function onSlotChangeAction(int $slot): bool {
		return false;
	}

	protected function onDropAction(Item $item): bool {
		return false;
	}
}
