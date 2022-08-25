<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\game\shop\content;

use Lyrica0954\StarPvE\game\player\equipment\SwordEquipment;
use Lyrica0954\StarPvE\game\player\GamePlayer;
use Lyrica0954\StarPvE\StarPvE;
use Lyrica0954\StarPvE\utils\PlayerUtil;
use pocketmine\item\Item;
use pocketmine\player\Player;

class PerkContent extends ShopContent {

	protected function getGamePlayer(Player $player): ?GamePlayer {
		$gamePlayerManager = StarPvE::getInstance()->getGamePlayerManager();
		return $gamePlayerManager->getGamePlayer($player);
	}

	public function canBuy(Player $player): bool {
		$canBuy = parent::canBuy($player);

		$gamePlayer = $this->getGamePlayer($player);
		if ($gamePlayer instanceof GamePlayer) {
			if ($gamePlayer->getPerkAvailable() <= 0) {
				$canBuy = false;
			}
		}

		return $canBuy;
	}

	protected function onBought(Player $player): bool {
		$gamePlayer = $this->getGamePlayer($player);
		$gamePlayer->sendPerkForm(false);
		return true;
	}

	public function getCost(Player $player): ?Item {
		return null;
	}
}
