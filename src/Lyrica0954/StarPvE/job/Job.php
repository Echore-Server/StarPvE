<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\job;

use Lyrica0954\StarPvE\data\condition\Condition;
use pocketmine\player\Player;

abstract class Job {

    abstract public function getName(): string;

    abstract public function getDescription(): string;

    abstract public function getAbilityName(): string; #todo: ability クラスに移動すべき？

    abstract public function getAbilityDescription(): string;

    abstract public function getSkillName(): string;

    abstract public function getSkillDescription(): string;

    public function isSelectable(Player $player): bool{
        $condition = $this->getSelectableCondition();
        if ($condition !== null){
            return $condition->check($player);
        } else {
            return true;
        }
    }

    abstract public function getSelectableCondition(): ?Condition; 
}