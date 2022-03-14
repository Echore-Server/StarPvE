<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\entity\item;

use pocketmine\entity\object\ItemEntity;
use pocketmine\player\Player;

class GhostItemEntity extends ItemEntity{

    public function isMergeable(ItemEntity $entity): bool{
        return false;
    }

    public function onCollideWithPlayer(Player $player): void{
        # NOOPOOP
    }
}