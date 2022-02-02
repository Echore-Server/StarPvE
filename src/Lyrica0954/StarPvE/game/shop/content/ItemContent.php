<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\game\shop\content;

use Lyrica0954\StarPvE\utils\PlayerUtil;
use pocketmine\item\Item;
use pocketmine\player\Player;

class ItemContent extends ShopContent {

    private Item $item;
    private Item $costItem;
    
    public function __construct(string $name, Item $item, Item $costItem){
        $this->item = $item;
        $this->costItem = $costItem;

        parent::__construct($name);
    }

    public function getCost(Player $player): ?Item{
        return clone $this->costItem;
    }

    protected function onBought(Player $player): bool{
        $item = clone $this->item;
        if ($player->getInventory()->canAddItem($item)){
            $player->getInventory()->addItem($item);
            return true;
        }
        return false;
    }
}