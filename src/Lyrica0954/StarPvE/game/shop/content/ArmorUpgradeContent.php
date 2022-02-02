<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\game\shop\content;

use Lyrica0954\StarPvE\game\player\equipment\ArmorEquipment;
use Lyrica0954\StarPvE\game\player\equipment\SwordEquipment;
use Lyrica0954\StarPvE\game\player\GamePlayer;
use Lyrica0954\StarPvE\StarPvE;
use Lyrica0954\StarPvE\utils\PlayerUtil;
use pocketmine\item\Item;
use pocketmine\player\Player;

class ArmorUpgradeContent extends ShopContent {

    protected function getGamePlayer(Player $player): ?GamePlayer{
        $gamePlayerManager = StarPvE::getInstance()->getGamePlayerManager();
        return $gamePlayerManager->getGamePlayer($player);
    }

    public function canBuy(Player $player): bool{
        $canBuy = parent::canBuy($player);

        $gamePlayer = $this->getGamePlayer($player);
        if ($gamePlayer instanceof GamePlayer){
            if (!$gamePlayer->getArmorEquipment()->canUpgrade()){
                $canBuy = false;
            }
        }

        return $canBuy;
    }

    protected function onBought(Player $player): bool{
        $gamePlayer = $this->getGamePlayer($player);
        $ae = $gamePlayer?->getArmorEquipment();
        $ae?->upgrade();
        return $ae instanceof ArmorEquipment;
    }

    public function getCost(Player $player): ?Item{
        $gamePlayer = $this->getGamePlayer($player);
        $ae = $gamePlayer?->getArmorEquipment();
        if ($ae instanceof ArmorEquipment){
            return $ae->getCost($ae->getLevel() + 1);
        } else {
            return null;
        }
    }
}