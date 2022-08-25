<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\service\indicator;

use Lyrica0954\Service\Service;
use Lyrica0954\StarPvE\data\adapter\PlayerConfigAdapter;
use Lyrica0954\StarPvE\data\player\SettingVariables;
use Lyrica0954\StarPvE\service\ListenerService;
use Lyrica0954\StarPvE\StarPvE;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\HandlerListManager;
use pocketmine\event\Listener;
use pocketmine\player\Player;

class OutboundDamageService extends ListenerService {

	/**
	 * @var array{playerHash: string, originalEvent: EntityDamageByEntityEvent}
	 */
	private array $originalStore;

	protected function init(): void {
		$this->originalStore = [];
	}


	/**
	 * @param EntityDamageByEntityEvent $event
	 * 
	 * @return void
	 * 
	 * @priority LOWEST
	 */
	public function originalEvent(EntityDamageByEntityEvent $event): void {
		$damager = $event->getDamager();
		if ($damager instanceof Player) {
			$adapter = SettingVariables::fetch($damager);
			$store = false;

			if ($adapter instanceof PlayerConfigAdapter) {
				$store = $adapter->getConfig()->get(SettingVariables::DEBUG_DAMAGE, false);
			}

			if ($store) {
				$this->originalStore[spl_object_hash($damager)] = clone $event;
			}
		}
	}

	/**
	 * @param EntityDamageByEntityEvent $event
	 * 
	 * @return void
	 * 
	 * @priority MONITOR
	 */
	public function finalEvent(EntityDamageByEntityEvent $event): void {
		$damager = $event->getDamager();

		if ($damager instanceof Player) {

			$originalEvent = $this->originalStore[spl_object_hash($damager)] ?? null;
			if ($originalEvent instanceof EntityDamageByEntityEvent) {
				$this->originalStore[spl_object_hash($damager)] = null;
				$originalDamage = $originalEvent->getFinalDamage();

				$finalDamage = $event->getFinalDamage();

				$diff = ($finalDamage - $originalDamage);

				$diffString = ($diff >= 0 ? "+" : "") . (string) $diff;

				$damager->sendMessage("§a§l>> §r§8{$originalEvent->getOriginalBaseDamage()} §f-> §7{$originalDamage} §f-> §a{$finalDamage} §d({$diffString})");
			}
		}
	}
}
