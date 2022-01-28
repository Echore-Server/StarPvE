<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\data\condition;

use pocketmine\player\Player;

interface Condition {

    public function check(Player $player): bool;
}