<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\service\message;

use Lyrica0954\StarPvE\game\Game;
use Lyrica0954\StarPvE\service\ListenerService;
use Lyrica0954\StarPvE\StarPvE;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\player\Player;

class PlayerAdviceMessageService extends ListenerService {

	/**
	 * @param EntityDamageByEntityEvent $event
	 * 
	 * @return void
	 * 
	 * @priority MONITOR
	 */
	public function onDamageByEntity(EntityDamageByEntityEvent $event): void {
		$entity = $event->getEntity();
		$damager = $event->getDamager();

		if ($entity instanceof Player) {
			$finalHealth = $entity->getHealth() - $event->getFinalDamage();
			$b = $entity->getMaxHealth() / 4;
			if (($finalHealth <= $b) && $entity->getHealth() > $b) {
				$gamePlayer = StarPvE::getInstance()->getGamePlayerManager()->getGamePlayer($entity);
				if (($game = $gamePlayer?->getGame()) instanceof Game) {
					$game->broadcastMessage("§7{$entity->getName()} §fが §cピンチ§f！！");
				}
			}
		}
	}
}
