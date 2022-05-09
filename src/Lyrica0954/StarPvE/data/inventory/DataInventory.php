<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\data\inventory;

use Lyrica0954\StarPvE\data\inventory\item\InvItem;
use pocketmine\inventory\Inventory;
use pocketmine\inventory\SimpleInventory;

interface DataInventory {

	const MAX_STACK = 64;

	public function addItem(InvItem ...$slots): array;

	public function canAddItem(InvItem $item): bool;

	public function getAddableItemQuantity(InvItem $item): int;

	public function setItem(int $index, InvItem $item): void;

	public function getItem(int $index): ?InvItem;

	public function getMaxStackSize(): int;

	public function setMaxStackSize(int $size): void;

	public function getSize(): int;

	/**
	 * @param bool $includeEmpty
	 * 
	 * @return InvItem[]
	 */
	public function getContents(bool $includeEmpty = false): array;

	/**
	 * @param InvItem[] $items
	 * 
	 * @return void
	 */
	public function setContents(array $items): void;

	public function contains(InvItem $item): bool;

	public function all(InvItem $item): array;

	public function isSlotEmpty(int $index): bool;

	public function clear(int $index): void;

	public function clearAll(): void;

	public function remove(InvItem $item): void;

	/**
	 * @param InvItem ...$slots
	 * 
	 * @return InvItem[]
	 */
	public function removeItem(InvItem ...$slots): array;

	public function swap(int $slot1, int $slot2): void;
}
