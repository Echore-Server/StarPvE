<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\game\shop\content;

use Lyrica0954\StarPvE\utils\Messanger;
use Lyrica0954\StarPvE\utils\PlayerUtil;
use pocketmine\item\Item;
use pocketmine\player\Player;

abstract class ShopContent {

    private string $name;

    public function __construct(string $name) {
        $this->name = $name;
    }

    public function getName(): string {
        return $this->name;
    }

    public function canBuy(Player $player): bool {
        $costItem = $this->getCost($player);
        if ($costItem !== null) {
            $has = PlayerUtil::countItem($player, $costItem->getId());

            return $has >= $costItem->getCount();
        } else {
            return true;
        }
    }

    public function buy(Player $player): void {
        if ($this->canBuy($player)) {
            $costItem = $this->getCost($player);
            if ($this->onBought($player)) {
                if ($costItem !== null) {
                    $player->getInventory()->removeItem(clone $costItem);
                }
                Messanger::talk($player, "村人", "§a{$this->name} を購入しました！");
            } else {
                Messanger::talk($player, "村人", "§c購入に失敗しました！");
            }
        } else {
            $costItem = $this->getCost($player);
            $message = $this->getBoughtFailureMessage($player);
            $itemMsg = "";
            if ($costItem !== null) {
                $has = PlayerUtil::countItem($player, $costItem->getId());
                $need = $costItem->getCount() - $has;
                if ($need > 0) {
                    $itemMsg = "あと §6{$need} §c{$costItem->getName()}が必要です！";
                }
            }
            Messanger::talk($player, "村人", "§cこのアイテムを購入できません！{$itemMsg}{$message}");
        }
    }

    protected function getBoughtFailureMessage(Player $player): string {
        return "";
    }

    abstract protected function onBought(Player $player): bool;

    abstract public function getCost(Player $player): ?Item;
}
