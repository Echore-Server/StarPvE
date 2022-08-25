<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\game\shop\content;

use Lyrica0954\StarPvE\game\player\equipment\SwordEquipment;
use Lyrica0954\StarPvE\game\player\GamePlayer;
use Lyrica0954\StarPvE\StarPvE;
use Lyrica0954\StarPvE\utils\PlayerUtil;
use pocketmine\item\Item;
use pocketmine\player\Player;

class SwordUpgradeContent extends ShopContent {

	protected function getGamePlayer(Player $player): ?GamePlayer {
		$gamePlayerManager = StarPvE::getInstance()->getGamePlayerManager();
		return $gamePlayerManager->getGamePlayer($player);
	}

	public function canBuy(Player $player): bool {
		$canBuy = parent::canBuy($player);

		$gamePlayer = $this->getGamePlayer($player);
		if ($gamePlayer instanceof GamePlayer) {
			if (!$gamePlayer->getSwordEquipment()->canUpgrade()) {
				$canBuy = false;
			}
		}

		return $canBuy;
	}

	protected function onBought(Player $player): bool {
		$gamePlayer = $this->getGamePlayer($player);
		$se = $gamePlayer?->getSwordEquipment();
		$se?->upgrade();
		return $se instanceof SwordEquipment;
	}

	public function getCost(Player $player): ?Item {
		$gamePlayer = $this->getGamePlayer($player);
		$se = $gamePlayer?->getSwordEquipment();
		if ($se instanceof SwordEquipment) {
			return $se->getCost($se->getLevel() + 1);
		} else {
			return null;
		}
	}
}
