<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\data\condition;

use pocketmine\player\Player;

class ConditionList implements Condition{

    private array $conditions;

    public function __construct(Condition ...$conditions){
        $this->conditions = $conditions;
    }

    public function check(Player $player): bool{
        foreach($this->conditions as $condition){
            if (!$condition->check($player)){
                return false;
            }
        }
        return true;
    }
}