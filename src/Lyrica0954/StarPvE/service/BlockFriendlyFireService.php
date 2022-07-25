<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\service;

use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\player\Player;

class BlockFriendlyFireService extends ListenerService {

	/**
	 * @param EntityDamageByEntityEvent $event
	 * 
	 * @return void
	 * 
	 * @priority LOWEST
	 */
	public function onDamageByEntity(EntityDamageByEntityEvent $event): void {
		$entity = $event->getEntity();
		$damager = $event->getDamager();

		if ($damager instanceof Player && $entity instanceof Player) {
			$event->cancel();
		}
	}
}
