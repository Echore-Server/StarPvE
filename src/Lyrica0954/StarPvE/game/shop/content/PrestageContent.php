<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\game\shop\content;

use Lyrica0954\StarPvE\form\YesNoForm;
use Lyrica0954\StarPvE\game\player\equipment\SwordEquipment;
use Lyrica0954\StarPvE\game\player\GamePlayer;
use Lyrica0954\StarPvE\identity\IdentityUtil;
use Lyrica0954\StarPvE\identity\player\AddAttackDamageArgIdentity;
use Lyrica0954\StarPvE\identity\player\AddMaxHealthArgIdentity;
use Lyrica0954\StarPvE\identity\player\ReducePercentageArgIdentity;
use Lyrica0954\StarPvE\StarPvE;
use Lyrica0954\StarPvE\utils\PlayerUtil;
use pocketmine\item\Item;
use pocketmine\item\VanillaItems;
use pocketmine\player\Player;

class PrestageContent extends ShopContent {

    protected function getGamePlayer(Player $player): ?GamePlayer {
        $gamePlayerManager = StarPvE::getInstance()->getGamePlayerManager();
        return $gamePlayerManager->getGamePlayer($player);
    }

    public function canBuy(Player $player): bool {
        $canBuy = parent::canBuy($player);

        $gamePlayer = $this->getGamePlayer($player);
        if ($gamePlayer instanceof GamePlayer) {
            if (!$gamePlayer->getSwordEquipment()->isMaxLevel() || !$gamePlayer->getArmorEquipment()->isMaxLevel()) {
                $canBuy = false;
            }
        }

        return $canBuy;
    }

    protected function onBought(Player $player): bool {
        $form = new YesNoForm("プレステージを実行すると、武器と防具の強化がリセットされます。\n§bプレステージ実行時:§r\n§7受けるダメージ §c-15%%\n§7最大体力 §c+6", function (Player $rplayer, $data) use ($player) {
            if ($rplayer === $player) {
                if ($data !== null) {
                    if ($data == 0) {
                        $gamePlayer = $this->getGamePlayer($player);
                        $gamePlayer->resetEquipment();
                        $gamePlayer->refreshEquipment();
                        $ig = $gamePlayer->getIdentityGroup();
                        $ig->reset();
                        $ig->add(IdentityUtil::playerArg(new ReducePercentageArgIdentity(null, 0.15), $player));
                        $ig->add(IdentityUtil::playerArg(new AddMaxHealthArgIdentity(null, 6), $player));
                        $ig->apply();
                        PlayerUtil::playSound($player, "random.enderchestclosed", 0.5, 1.0);
                        PlayerUtil::playSound($player, "random.totem", 0.9, 0.3);
                        $player->sendMessage("§9プレステージを実行しました！ §7受けるダメージ §c-15% §7最大体力 §c+6");
                    }
                }
            }
        });
        $player->sendForm($form);
        return true;
    }

    public function getCost(Player $player): ?Item {
        return VanillaItems::EMERALD()->setCount(40);
    }

    protected function getBoughtFailureMessage(Player $player): string {
        return "武器と防具の強化を最大までする必要があります！";
    }
}
