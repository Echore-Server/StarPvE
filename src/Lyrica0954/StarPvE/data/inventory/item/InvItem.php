<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\data\inventory\item;

use Lyrica0954\SmartEntity\entity\fightstyle\Style;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIdentifier;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\StringTag;

abstract class InvItem {

	private int $id;

	protected int $count;

	protected string $displayName = "";

	/**
	 * @var string[]
	 */
	protected array $lore = [];

	/**
	 * @var ItemIdentifier|null
	 */
	protected ?ItemIdentifier $entryItemIdentifier = null;

	public function __construct(int $id) {
		$this->id = $id;
		$this->count = 1;
	}

	public function getId(): int {
		return $this->id;
	}

	public function getCount(): int {
		return $this->count;
	}

	/**
	 * @return string[]
	 */
	public function getLore(): array {
		return $this->lore;
	}

	public function setLore(array $lore): void {
		$this->lore = $lore;
	}

	public function addLore(string $text): void {
		$this->lore[] = $text;
	}

	public function save(): array {
		$base = [
			"id" => $this->id,
			"count" => $this->count,
			"displayName" => $this->displayName,
			"lore" => $this->lore
		];

		if ($this->entryItemIdentifier !== null) {
			$base["entryItemIdentifier"] = [
				"id" => $this->entryItemIdentifier->getId(),
				"meta" => $this->entryItemIdentifier->getMeta()
			];
		}

		return $base;
	}

	abstract public function getName(): string;

	abstract public function getDescription(): string;

	abstract public function getMaxStackSize(): int;

	public function getDisplayName(): string {
		return $this->displayName;
	}

	public function equals(InvItem $i, bool $checkItemIdentifier = false, bool $strict = false): bool {
		$result = true;

		if ($i->getName() != $this->getName()) {
			$result = false;
		}

		if ($i->getId() != $this->getId()) {
			$result = false;
		}

		if ($checkItemIdentifier && ($i->getEntryItemIdentifier() != $this->getEntryItemIdentifier())) {
			$result = false;
		}

		if ($strict && ($i->getDescription() != $this->getDescription())) {
			$result = false;
		}

		if ($strict && ($i->getDisplayName() != $this->getDisplayName())) {
			$result = false;
		}

		return $result;
	}

	final public function canStackWith(InvItem $other): bool {
		return $this->equals($other, true, true);
	}

	public function pop(int $count = 1): void {
		$this->count = max(0, $this->count - $count);
	}

	public function setCount(int $count): void {
		$this->count = $count;
	}

	public function setDisplayName(string $displayName): void {
		$this->displayName = $displayName;
	}

	public function getEntryItemIdentifier(): ?ItemIdentifier {
		return $this->entryItemIdentifier;
	}

	public function createEntryItem(): ?Item {
		if ($this->entryItemIdentifier instanceof ItemIdentifier) {
			$f = ItemFactory::getInstance();
			/**
			 * @var ItemFactory $f
			 */
			$item = $f->get($this->entryItemIdentifier->getId(), $this->entryItemIdentifier->getMeta());
			$item->setCustomName($this->displayName);
			$item->setLore(array_merge(["§7" . $this->getDescription()], $this->lore));
			$item->setCount($this->count);
			#$tag = $item->getNamedTag();
			#$tag->setTag("hostId", new IntTag($this->id));
			#$tag->setTag("hostName", new StringTag($this->getName()));
			#$item->setNamedTag($tag);
			return $item;
		} else {
			return null;
		}
	}
}
