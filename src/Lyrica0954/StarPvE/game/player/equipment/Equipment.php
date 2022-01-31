<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\game\player\equipment;

use Lyrica0954\StarPvE\game\Game;
use Lyrica0954\StarPvE\game\player\GamePlayer;
use pocketmine\item\Item;
use pocketmine\player\Player;

abstract class Equipment {

    protected GamePlayer $gamePlayer;
    protected Player $player;
    protected int $level;
    protected int $maxLevel;

    public function __construct(GamePlayer $gamePlayer){
        $this->gamePlayer = $gamePlayer;
        $this->player = $gamePlayer->getPlayer();
        $this->level = $this->getInitialLevel();
        $this->maxLevel = $this->getInitialMaxLevel();
    }

    protected function getInitialLevel(): int{
        return 1;
    }

    abstract protected function getInitialMaxLevel(): int;

    abstract public function getCost(int $level): Item;

    public function isMaxLevel(): bool{
        return $this->level >= $this->maxLevel;
    }

    public function canUpgradeTo(int $level): bool{
        if ($level > $this->maxLevel){
            return false;
        }

        $costItem = $this->getCost($level);
        $has = 0;
        foreach($this->player->getInventory()->getContents() as $item){
            if ($costItem->getId() === $item->getId()){
                $has += $item->getCount();
            }
        }

        return $has >= $costItem->getCount();
    }

    public function canUpgrade(): bool{
        return $this->canUpgradeTo($this->level + 1);
    }

    public function upgradeTo(int $level): void{
        if ($this->canUpgradeTo($level)){
            $this->level = $level;

            $this->onUpgrade($level);
        }
    }

    public function upgrade(): void{
        $this->upgradeTo($this->level + 1);
    }

    abstract protected function onUpgrade(int $level): void;

    abstract public function getName(): string;
}