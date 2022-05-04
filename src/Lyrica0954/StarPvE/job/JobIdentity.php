<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\job;

use Lyrica0954\StarPvE\data\condition\Condition;
use Lyrica0954\StarPvE\identity\Identity;
use Lyrica0954\StarPvE\job\player\PlayerJob;
use Lyrica0954\StarPvE\StarPvE;
use pocketmine\event\HandlerListManager;
use pocketmine\event\Listener;
use pocketmine\player\Player;
use pocketmine\Server;

abstract class JobIdentity extends Identity {
    
    protected PlayerJob $playerJob;

    public function __construct(PlayerJob $playerJob){
        $this->playerJob = $playerJob;
        parent::__construct();
    }

    public function applyJob(): void{
        $this->apply($this->playerJob->getPlayer());
    }

    public function resetJob(): void{
        $this->reset($this->playerJob->getPlayer());
    }

    public function isActivateable(): bool{
        if (!$this->playerJob->getPlayer() instanceof Player){
            return true;
        }

        return $this->isActivateableFor($this->playerJob->getPlayer());
    }
}