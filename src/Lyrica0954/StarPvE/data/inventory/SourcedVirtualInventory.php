<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\data\inventory;

use Lyrica0954\StarPvE\utils\TaskUtil;
use pocketmine\inventory\Inventory;
use pocketmine\inventory\PlayerCursorInventory;
use pocketmine\inventory\PlayerInventory;
use pocketmine\inventory\transaction\action\InventoryAction;
use pocketmine\item\Item;
use pocketmine\scheduler\ClosureTask;

class SourcedVirtualInventory extends VirtualInventory {

	protected function onDropAction(Item $item): bool {
		return false;
	}

	protected function onTransfer(int $fromSlot, int $toSlot, Item $fromItem, Item $toItem, Inventory $from, Inventory $to): bool {
		if (($to instanceof PlayerInventory || $to instanceof PlayerCursorInventory) && $from === $this) {
			TaskUtil::delayed(new ClosureTask(function () use ($fromSlot, $toItem) {
				$this->setItem($fromSlot, clone $toItem);
			}), 1);
			return true;
		}

		return false;
	}

	protected function onRawAction(InventoryAction $action): bool {
		return true;
	}
}
