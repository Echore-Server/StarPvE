<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\service\indicator;

use Lyrica0954\StarPvE\data\player\adapter\GenericConfigAdapter;
use Lyrica0954\StarPvE\data\player\adapter\JobConfigAdapter;
use Lyrica0954\StarPvE\event\global\GlobalAddExpEvent;
use Lyrica0954\StarPvE\job\player\PlayerJob;
use Lyrica0954\StarPvE\service\ListenerService;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\player\Player;
use pocketmine\Server;

class ExpIndicatorService extends ListenerService {

	public function onGetExp(GlobalAddExpEvent $event) {
		$adapter = $event->getAdapter();

		if ($adapter instanceof GenericConfigAdapter) {
			$conf = $adapter->getConfig();
			$username = $conf->get(GenericConfigAdapter::USERNAME);
			if (is_string($username)) {
				$player = Server::getInstance()->getPlayerExact($username);
				if ($player instanceof Player) {
					$this->update($player, $event->getAmount());
				}
			}
		}
	}

	public function update(Player $player, float $add = 0) {
		$adapter = GenericConfigAdapter::fetch($player);
		if ($adapter instanceof GenericConfigAdapter) {
			$conf = $adapter->getConfig();
			$exp = $conf->get(GenericConfigAdapter::EXP) + $add;
			$nextExp = $conf->get(GenericConfigAdapter::NEXT_EXP);
			$level = $conf->get(GenericConfigAdapter::LEVEL);

			$perc = min(1.0, $exp / $nextExp);
			$expm = $player->getXpManager();
			$expm->setXpAndProgress($level, $perc);
		}
	}

	public function onQuit(PlayerQuitEvent $event) {
		$player = $event->getPlayer();

		$player->getXpManager()->setXpAndProgress(0, 0.0);
	}

	public function onJoin(PlayerJoinEvent $event) {
		$player = $event->getPlayer();

		$this->update($player);
	}
}
