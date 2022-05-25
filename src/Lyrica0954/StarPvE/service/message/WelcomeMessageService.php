<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\service\message;

use Lyrica0954\StarPvE\job\player\PlayerJob;
use Lyrica0954\StarPvE\service\ListenerService;
use pocketmine\event\player\PlayerJoinEvent;

class WelcomeMessageService extends ListenerService {

	public function onJoin(PlayerJoinEvent $event) {
		$player = $event->getPlayer();

		#todo: 
	}
}
