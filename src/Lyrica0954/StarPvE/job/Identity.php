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
    
    protected PlayerJob $playerJob;
    protected ?Condition $activateCondition;

    public function close(): void{
        if ($this instanceof Listener) HandlerListManager::global()->unregisterAll($this);
    }

    protected function registerEvent(): void{
        if ($this instanceof Listener) Server::getInstance()->getPluginManager()->registerEvents($this, StarPvE::getInstance());
    }

    private function init(PlayerJob $playerJob){
        $this->playerJob = $playerJob;
    }

    public static function setCondition(Identity $identity, ?Condition $condition): Identity{
        $identity->setActivateCondition($condition);
        return $identity;
    }

    public function __construct(PlayerJob $playerJob){
        $this->init($playerJob);
        $this->activateCondition = null;
    }

    public function setActivateCondition(?Condition $condition){
        $this->activateCondition = $condition;
    }

    public function getActivateCondition(): ?Condition{
        return $this->activateCondition;
    }

    abstract public function apply(): void;

    abstract public function reset(): void;

    public function isActivateableFor(Player $player): bool{
        $condition = $this->getActivateCondition();

        if ($condition !== null){
            return $condition->check($player);
        } else {
            return true;
        }
    }

    public function isActivateable(): bool{
        if (!$this->playerJob->getPlayer() instanceof Player){
            return true;
        }
        return $this->isActivateableFor($this->playerJob->getPlayer());
    }

    abstract public function getName(): string;

    abstract public function getDescription(): string;
}