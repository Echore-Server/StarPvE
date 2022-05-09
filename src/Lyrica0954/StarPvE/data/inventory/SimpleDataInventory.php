<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\data\inventory;

use Lyrica0954\StarPvE\data\inventory\item\InvItem;

class SimpleDataInventory extends BaseDataInventory {

	/**
	 * @var \SplFixedArray|(InvItem|null)[]
	 */
	protected \SplFixedArray $slots;

	public function __construct(int $size) {
		$this->slots = new \SplFixedArray($size);

		parent::__construct();
	}

	public function getSize(): int {
		return $this->slots->getSize();
	}

	public function getItem(int $index): ?InvItem {
		return $this->slots[$index];
	}

	protected function internalSetItem(int $index, InvItem $item): void {
		$this->slots[$index] = $item;
	}

	protected function internalSetNull(int $index): void {
		$this->slots[$index] = null;
	}

	/**
	 * @return (InvItem|null)[]
	 */
	public function getContents(bool $includeEmpty = false): array {
		$contents = [];

		foreach ($this->slots as $i => $slot) {
			if ($slot !== null) {
				$contents[$i] = clone $slot;
			} elseif ($includeEmpty) {
				$contents[$i] = null;
			}
		}

		return $contents;
	}

	/**
	 * @param (InvItem|null)[] $items
	 * 
	 * @return void
	 */
	protected function internalSetContents(array $items): void {
		for ($i = 0, $size = $this->getSize(); $i < $size; ++$i) {
			if (!isset($items[$i]) || $items[$i] == null) {
				$this->slots[$i] = null;
			} else {
				$this->slots[$i] = clone $items[$i];
			}
		}
	}
}
