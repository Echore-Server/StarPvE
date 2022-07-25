<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\service;

use Lyrica0954\Service\Service;
use pocketmine\event\HandlerListManager;
use pocketmine\event\Listener;

abstract class ListenerService extends Service implements Listener {

    protected function onEnable(): void {
        $plugin = $this->getSession()->getPlugin();

        $plugin->getServer()->getPluginManager()->registerEvents($this, $plugin);
    }

    protected function onDisable(): void {
        HandlerListManager::global()->unregisterAll($this);
    }
}
