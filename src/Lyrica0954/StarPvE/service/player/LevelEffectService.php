<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\service\player;

use Lyrica0954\StarPvE\data\player\adapter\GenericConfigAdapter;
use Lyrica0954\StarPvE\data\player\adapter\JobConfigAdapter;
use Lyrica0954\StarPvE\service\ListenerService;
use Lyrica0954\StarPvE\utils\EntityUtil;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\player\Player;

class LevelEffectService extends ListenerService {

	public function onEntityDamageByEntity(EntityDamageByEntityEvent $event) {
		$entity = $event->getEntity();
		$damager = $event->getDamager();

		if ($damager instanceof Player) {
			EntityUtil::multiplyFinalDamage($event, self::getDamagePerc($damager));
		}
	}

	public static function getDamagePerc(Player $player): float {
		$adapter = GenericConfigAdapter::fetch($player);

		if ($adapter instanceof GenericConfigAdapter) {
			$level = $adapter->getConfig()->get(GenericConfigAdapter::LEVEL, 0);

			$dmgPerc = 1.0 + (($level - 1) * 0.005);
			return $dmgPerc;
		}
		return 1.0;
	}
}
