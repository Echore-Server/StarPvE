<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\data\inventory;

use Lyrica0954\StarPvE\data\inventory\item\InvItem;
use pocketmine\inventory\Inventory;
use pocketmine\item\Item;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\IntTag;

abstract class BaseDataInventory implements DataInventory {

	/**
	 * @var int
	 */
	protected int $maxStackSize = DataInventory::MAX_STACK;

	/**
	 * @var \SplFixedArray|(InvItem|null)[]
	 */
	protected \SplFixedArray $slots;

	public function __construct() {
	}

	public function getMaxStackSize(): int {
		return $this->maxStackSize;
	}

	public function setMaxStackSize(int $size): void {
		$this->maxStackSize = $size;
	}

	abstract protected function internalSetItem(int $index, InvItem $item): void;

	abstract protected function internalSetNull(int $index): void;

	public function setItem(int $index, ?InvItem $item): void {
		$finalItem = ($item instanceof InvItem) ? (clone $item) : $item;
		/**
		 * @var InvItem|null $finalItem
		 */
		if ($item->getCount() <= 0) {
			$finalItem = null;
		}

		if ($finalItem !== null) {
			$this->internalSetItem($index, $item);
		} else {
			$this->internalSetNull($index);
		}
	}

	/**
	 * @param InvItem[] $items
	 * 
	 * @return void
	 */
	abstract protected function internalSetContents(array $items): void;

	public function setContents(array $items): void {
		if (count($items) > $this->getSize()) {
			$items = array_slice($items, 0, $this->getSize(), true);
		}

		$this->internalSetContents($items);
	}

	public function contains(InvItem $item): bool {
		$count = max(1, $item->getCount());
		foreach ($this->getContents() as $i) {
			if ($item->equals($i)) {
				$count -= $i->getCount();
				if ($count <= 0) {
					return true;
				}
			}
		}

		return false;
	}

	public function canAddItem(InvItem $item): bool {
		return $this->getAddableItemQuantity($item) === $item->getCount();
	}

	public function all(InvItem $item): array {
		$slots = [];
		foreach ($this->getContents() as $index => $i) {
			if ($item->equals($i)) {
				$slots[$index] = $i;
			}
		}

		return $slots;
	}

	public function isSlotEmpty(int $index): bool {
		return $this->getItem($index) === null;
	}

	public function getAddableItemQuantity(InvItem $item): int {
		$count = $item->getCount();
		for ($i = 0, $size = $this->getSize(); $i < $size; ++$i) {
			$slot = $this->getItem($i);
			if ($slot !== null) {
				if ($item->canStackWith($slot)) {
					if (($diff = min($slot->getMaxStackSize(), $item->getMaxStackSize()) - $slot->getCount()) > 0) {
						$count -= $diff;
					}
				}
			} else {
				$count -= min($this->getMaxStackSize(), $item->getMaxStackSize());
			}

			if ($count <= 0) {
				return $item->getCount();
			}
		}

		return $item->getCount() - $count;
	}

	public function addItem(InvItem ...$slots): array {
		/** @var InvItem[] $itemSlots */
		/** @var InvItem[] $slots */
		$itemSlots = [];
		foreach ($slots as $slot) {
			if ($slot instanceof InvItem) {
				$itemSlots[] = clone $slot;
			}
		}

		/** @var InvItem[] $returnSlots */
		$returnSlots = [];

		foreach ($itemSlots as $item) {
			$leftover = $this->internalAddItem($item);
			if ($leftover instanceof InvItem) {
				$returnSlots[] = $leftover;
			}
		}

		return $returnSlots;
	}

	public function injectToInventory(Inventory $inventory): void {
		$contents = [];
		foreach ($this->getContents() as $k => $item) {
			$entry = $item->createEntryItem();
			if ($entry !== null) {
				$tag = $entry->getCustomBlockData() ?? new CompoundTag();
				$tag->setTag("hostInventoryIndex", new IntTag($k));
				$entry->setCustomBlockData($tag);

				$contents[$k] = $entry;
			}
		}

		$inventory->setContents($contents);
	}

	private function internalAddItem(InvItem $slot): InvItem {
		$emptySlots = [];

		for ($i = 0, $size = $this->getSize(); $i < $size; ++$i) {
			$item = $this->getItem($i);
			if ($item === null) {
				$emptySlots[] = $i;
				continue;
			}

			if ($slot->canStackWith($item) && $item->getCount() < $item->getMaxStackSize()) {
				$amount = min($item->getMaxStackSize() - $item->getCount(), $slot->getCount(), $this->getMaxStackSize());
				if ($amount > 0) {
					$slot->setCount($slot->getCount() - $amount);
					$item->setCount($item->getCount() + $amount);
					$this->setItem($i, $item);
					if ($slot->getCount() <= 0) {
						break;
					}
				}
			}
		}

		if (count($emptySlots) > 0) {
			foreach ($emptySlots as $slotIndex) {
				$amount = min($slot->getMaxStackSize(), $slot->getCount(), $this->getMaxStackSize());
				$slot->setCount($slot->getCount() - $amount);
				$item = clone $slot;
				$item->setCount($amount);
				$this->setItem($slotIndex, $item);
				if ($slot->getCount() <= 0) {
					break;
				}
			}
		}

		return $slot;
	}

	public function remove(InvItem $item): void {
		foreach ($this->getContents() as $index => $i) {
			if ($item->equals($i)) {
				$this->clear($index);
			}
		}
	}

	public function removeItem(InvItem ...$slots): array {
		/** @var InvItem[] $itemSlots */
		/** @var InvItem[] $slots */
		$itemSlots = [];
		foreach ($slots as $slot) {
			if ($slot instanceof InvItem) {
				$itemSlots[] = clone $slot;
			}
		}

		for ($i = 0, $size = $this->getSize(); $i < $size; ++$i) {
			$item = $this->getItem($i);
			if ($item === null) {
				continue;
			}

			/**
			 * @var InvItem $item
			 */

			foreach ($itemSlots as $index => $slot) {
				/**
				 * @var InvItem $slot
				 */
				if ($slot->equals($item, true, true)) {
					$amount = min($item->getCount(), $slot->getCount());
					$slot->setCount($slot->getCount() - $amount);
					$item->setCount($item->getCount() - $amount);
					$this->setItem($i, $item);
					if ($slot->getCount() <= 0) {
						unset($itemSlots[$index]);
					}
				}
			}

			if (count($itemSlots) === 0) {
				break;
			}
		}

		return $itemSlots;
	}

	public function clear(int $index): void {
		$this->setItem($index, null);
	}

	public function clearAll(): void {
		$this->setContents([]);
	}

	public function swap(int $slot1, int $slot2): void {
		$i1 = $this->getItem($slot1);
		$i2 = $this->getItem($slot2);
		$this->setItem($slot1, $i2);
		$this->setItem($slot2, $i1);
	}
}
