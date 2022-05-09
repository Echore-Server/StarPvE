<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\data\inventory;

use pocketmine\block\inventory\ChestInventory;
use pocketmine\inventory\SimpleInventory;

class VirtualInventory {

	public static function convertDataInventory(SimpleDataInventory $inventory, SimpleInventory $core): SimpleInventory {
		$contents = $inventory->getContents(true);
		$coreContents = [];

		foreach ($contents as $i => $invItem) {
			$item = $invItem->createEntryItem();
			$coreContents[$i] = $item;
		}

		$core->setContents($coreContents);
		return $core;
	}

	public static function virtualization(SimpleInventory $inventory) {
	}
}
