<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\game\player\equipment;

use Lyrica0954\StarPvE\utils\PlayerUtil;
use pocketmine\item\Durable;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;

class SwordEquipment extends Equipment {

    public function getName(): string {
        return "武器";
    }

    protected function getInitialMaxLevel(): int {
        return 3;
    }

    public function getCost(int $level): Item {
        $f = ItemFactory::getInstance();
        $costItem = match ($level) {
            1 => $f->get(ItemIds::EMERALD, 0, 0),
            2 => $f->get(ItemIds::EMERALD, 0, 40),
            3 => $f->get(ItemIds::EMERALD, 0, 120),
            default => $f->get(ItemIds::EMERALD, 0, 0)
        };

        return $costItem;
    }

    protected function onUpgrade(int $level): void {
        $f = ItemFactory::getInstance();
        $item = match ($level) {
            1 => $f->get(ItemIds::WOODEN_SWORD),
            2 => $f->get(ItemIds::STONE_SWORD),
            3 => $f->get(ItemIds::DIAMOND_SWORD),
            default => $f->get(ItemIds::AIR)
        };

        if (!$item instanceof Durable) {
            return;
        }

        $item->setUnbreakable();


        $index = PlayerUtil::findSwordIndex($this->player);
        if ($index === null) {
            $this->player->getInventory()->addItem($item);
        } else {
            $this->player->getInventory()->setItem($index, $item);
        }
    }
}
