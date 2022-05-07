<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\data\inventory;

use Lyrica0954\SmartEntity\entity\fightstyle\Style;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIdentifier;

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

	public function __construct(int $id, string $name){
		$this->id = $id;
		$this->count = 1;
		$this->displayName = $name;
	}

	public function getId(): int{
		return $this->id;
	}

	public function getCount(): int{
		return $this->count;
	}

	abstract public function getName(): string;

	abstract public function getDescription(): string;

	public function getDisplayName(): string{
		return $this->displayName;
	}

	public function pop(int $count = 1): void{
		$this->count = max(0, $this->count - $count);
	}

	public function setCount(int $count): void{
		$this->count = $count;
	}

	public function setDisplayName(string $displayName): void{
		$this->displayName = $displayName;
	}

	public function getEntryItemIdentifier(): ?ItemIdentifier{
		return $this->entryItemIdentifier;
	}

	public function createEntryItem(): ?Item{
		if ($this->entryItemIdentifier instanceof ItemIdentifier){
			$f = ItemFactory::getInstance();
			/**
			 * @var ItemFactory $f
			 */
			$item = $f->get($this->entryItemIdentifier->getId(), $this->entryItemIdentifier->getMeta());
			$item->setCustomName($this->displayName);
			$item->setLore($this->lore);
			$item->setCount($this->count);
			return $item;
		} else {
			return null;
		}
	}
}