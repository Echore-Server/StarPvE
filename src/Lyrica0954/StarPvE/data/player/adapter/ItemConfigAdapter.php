<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\data\player\adapter;

use Lyrica0954\StarPvE\data\adapter\PlayerConfigAdapter;
use Lyrica0954\StarPvE\data\inventory\item\InvItem;
use Lyrica0954\StarPvE\data\inventory\SimpleDataInventory;
use pocketmine\utils\Config;

class ItemConfigAdapter extends PlayerConfigAdapter {

    protected SimpleDataInventory $inventory;

    public function __construct(string $xuid, Config $config) {
        parent::__construct($xuid, $config);

        $this->inventory = new SimpleDataInventory(54);
    }

    public function load(): void {
        for ($i = 0; $i < 54; $i++) {
            $nested = $this->config->getNested($i, null);
            if ($nested !== null) {
            }
        }
    }

    public function setItem(int $index, InvItem $item): void {
    }
}
