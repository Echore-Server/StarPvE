<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\job;

use Lyrica0954\StarPvE\data\condition\Condition;
use Lyrica0954\StarPvE\job\player\PlayerJob;
use Lyrica0954\StarPvE\StarPvE;
use pocketmine\event\HandlerListManager;
use pocketmine\event\Listener;
use pocketmine\player\Player;
use pocketmine\Server;

abstract class Identity {

    public function close(): void{
        if ($this instanceof Listener) HandlerListManager::global()->unregisterAll($this);
    }

    protected function registerEvent(): void{
        if ($this instanceof Listener) Server::getInstance()->getPluginManager()->registerEvents($this, StarPvE::getInstance());
    }

    abstract public function apply(PlayerJob $playerJob): void;

    abstract public function reset(PlayerJob $playerJob): void;

    abstract public function getActivateCondition(): ?Condition;

    public function isActivateable(Player $player): bool{
        $condition = $this->getActivateCondition();
        if ($condition !== null){
            return $condition->check($player);
        } else {
            return true;
        }
    }

    abstract public function getName(): string;

    abstract public function getDescription(): string;
}