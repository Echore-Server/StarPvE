<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\data\inventory\item;

use pocketmine\item\ItemIdentifier;
use pocketmine\item\ItemIds;

class InvApple extends MaterialItem {

    public function __construct(int $id) {
        parent::__construct($id);

        $this->entryItemIdentifier = new ItemIdentifier(ItemIds::APPLE, 0);
    }
}
