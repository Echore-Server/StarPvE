<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\data\player\adapter;

use Lyrica0954\StarPvE\data\adapter\PlayerConfigAdapter;
use Lyrica0954\StarPvE\data\inventory\item\InvItem;
use Lyrica0954\StarPvE\data\inventory\item\InvItemFactory;
use Lyrica0954\StarPvE\data\inventory\SimpleDataInventory;
use Lyrica0954\StarPvE\data\inventory\VirtualInventory;
use pocketmine\item\ItemFactory;
use pocketmine\utils\Config;

class ItemConfigAdapter extends PlayerConfigAdapter {

	protected int $size = 128;

	protected SimpleDataInventory $inventory;

	public function __construct(string $xuid, Config $config, int $size = 128) {
		parent::__construct($xuid, $config);

		$this->inventory = new SimpleDataInventory($size);

		$this->load();
	}

	public function load(): void {
		for ($i = 0; $i < $this->inventory->getSize(); $i++) {
			$nested = $this->config->getNested((string) $i, null);
			if ($nested !== null) {
				$parsed = InvItemFactory::getInstance()->getFromJson($nested);
				$this->inventory->setItem($i, $parsed);
			}
		}
	}

	public function save(): void {
		foreach ($this->inventory->getContents(true) as $index => $item) {
			$index = (string) $index;
			if ($item instanceof InvItem) {
				$this->config->setNested($index, $item->save());
			} else {
				$this->config->setNested($index, null);
			}
		}
	}

	public function getInventory(): SimpleDataInventory {
		return $this->inventory;
	}
}
