<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\job\identity;

use Lyrica0954\StarPvE\data\condition\Condition;
use Lyrica0954\StarPvE\job\Identity;
use Lyrica0954\StarPvE\job\player\PlayerJob;
use Lyrica0954\StarPvE\utils\EntityUtil;
use pocketmine\player\Player;

class AddMaxHealthIdentity extends Identity{

    protected int $add;

    public function __construct(PlayerJob $playerJob, int $add){
        parent::__construct($playerJob);
        $this->add = $add;
    }

    public function getName(): string{
		return "最大HP増加";
	}

    public function getDescription(): string{
		return "最大HPが {$this->add} 増加";
	}

    public function isActivateable(): bool{
        if (!$this->playerJob->getPlayer() instanceof Player){
            return false;
        }

        return parent::isActivateable();
    }

    public function apply(): void{
        EntityUtil::addMaxHealthSynchronously($this->playerJob->getPlayer(), $this->add);
    }

    public function reset(): void{
        EntityUtil::addMaxHealthSynchronously($this->playerJob->getPlayer(), -$this->add);
    }
}