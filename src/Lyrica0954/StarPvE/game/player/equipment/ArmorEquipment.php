<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\game\player\equipment;

use Lyrica0954\StarPvE\utils\ArmorSet;
use Lyrica0954\StarPvE\utils\PlayerUtil;
use pocketmine\item\Durable;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;

class ArmorEquipment extends Equipment {

    public function getName(): string{
        return "防具";
    }

    protected function getInitialMaxLevel(): int{
        return 3;
    }

    public function getCost(int $level): Item{
        $f = ItemFactory::getInstance();
        $costItem = match($level){
            1 => $f->get(ItemIds::EMERALD, 0, 0),
            2 => $f->get(ItemIds::EMERALD, 0, 40),
            3 => $f->get(ItemIds::EMERALD, 0, 120),
            default => $f->get(ItemIds::EMERALD, 0, 0)
        };

        return $costItem;
    }
    
    protected function onUpgrade(int $level): void{
        $armorSet = match($level){
            1 => ArmorSet::leather(),
            2 => ArmorSet::iron(),
            3 => ArmorSet::diamond(),
            default => new ArmorSet(null, null, null, null)
        };

        $armorSet->setUnbreakable();

        $armorSet->equip($this->player);
    }
}