<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\data\condition;

use Lyrica0954\StarPvE\data\player\PlayerDataCollector;
use pocketmine\player\Player;

class LevelCondition implements Condition{

    private int $min;

    public function __construct(int $min){
        $this->min = $min;
    }

    public function check(Player $player): bool{
        $level = PlayerDataCollector::getGenericConfig($player, "Level");
        return $level >= $this->min;
    }
}