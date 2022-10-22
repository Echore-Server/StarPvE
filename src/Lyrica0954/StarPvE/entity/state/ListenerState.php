<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\entity\state;

use Lyrica0954\StarPvE\entity\EntityState;
use Lyrica0954\StarPvE\StarPvE;
use pocketmine\event\HandlerListManager;
use pocketmine\event\Listener;
use pocketmine\Server;

abstract class ListenerState extends EntityState implements Listener {

	public function start(): void {
		Server::getInstance()->getPluginManager()->registerEvents($this, StarPvE::getInstance());
	}

	public function close(): void {
		HandlerListManager::global()->unregisterAll($this);
	}
}
