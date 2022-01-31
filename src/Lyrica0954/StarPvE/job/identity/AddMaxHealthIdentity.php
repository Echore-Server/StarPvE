<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\job\identity;

use Lyrica0954\StarPvE\data\condition\Condition;
use Lyrica0954\StarPvE\job\Identity;
use Lyrica0954\StarPvE\job\player\PlayerJob;
use pocketmine\player\Player;

abstract class AddMaxHealthIdentity extends Identity{

    protected int $add;

    public function __construct(int $add){
        $this->add = $add;
    }

    public function apply(PlayerJob $playerJob): void{
        $playerJob->getPlayer()->setMaxHealth($playerJob->getPlayer()->getMaxHealth() + $this->add);
    }

    public function reset(PlayerJob $playerJob): void{
        $playerJob->getPlayer()->setMaxHealth(20);
    }
}