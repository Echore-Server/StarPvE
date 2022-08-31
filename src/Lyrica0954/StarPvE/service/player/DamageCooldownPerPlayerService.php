<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\service\player;

use Lyrica0954\StarPvE\data\player\adapter\GenericConfigAdapter;
use Lyrica0954\StarPvE\game\player\GamePlayer;
use Lyrica0954\StarPvE\service\ListenerService;
use Lyrica0954\StarPvE\StarPvE;
use Lyrica0954\StarPvE\utils\EntityUtil;
use Lyrica0954\StarPvE\utils\SlowdownRunIds;
use pocketmine\entity\Living;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\ToastRequestPacket;
use pocketmine\player\Player;
use pocketmine\Server;

class DamageCooldownPerPlayerService extends ListenerService {

	/**
	 * @var array[][]
	 */
	protected array $cooldowns;

	/**
	 * @param EntityDamageEvent $event
	 * 
	 * @return [type]
	 * 
	 * @priority LOWEST
	 */
	public function onDamage(EntityDamageEvent $event) {
		$tick = Server::getInstance()->getTick();
		if ($event instanceof EntityDamageByEntityEvent) {
			$damager = $event->getDamager();
			$entity = $event->getEntity();

			if (!$entity instanceof Player && $entity instanceof Living) {
				if ($damager instanceof Player) {
					$hash = spl_object_hash($damager);
					$ehash = spl_object_hash($entity);
					$this->cooldowns[$hash] ?? $this->cooldowns[$hash] = [];

					$last = $this->cooldowns[$hash][$ehash] ?? [0, 6];
					$lastTick = $last[0];
					if ($tick - $lastTick < $last[1]) {
						$event->cancel();
					} else {
						$this->cooldowns[$hash][$ehash] = [$tick, 6];
					}
				}

				$event->setKnockBack(0);
			}

			if ($entity instanceof Player) {
				$event->setKnockBack($event->getKnockBack() / 3);
			}
		} else {
			$event->setAttackCooldown(0);
		}
	}

	/**
	 * @param EntityDamageByEntityEvent $event
	 * 
	 * @return [type]
	 * 
	 * @priority MONITOR
	 */
	public function onDamagePluginModified(EntityDamageByEntityEvent $event) {
		$damager = $event->getDamager();
		$entity = $event->getEntity();

		$tick = Server::getInstance()->getTick();
		if (!$entity instanceof Player && $entity instanceof Living) {
			if ($damager instanceof Player) {
				$hash = spl_object_hash($damager);
				$ehash = spl_object_hash($entity);

				$ct = $event->getAttackCooldown() !== 10 ? $event->getAttackCooldown() : 6;

				EntityUtil::slowdown($entity, $ct, 0.4, SlowdownRunIds::get($this::class));
				$this->cooldowns[$hash][$ehash][1] = $ct;
			}
		}

		$event->setAttackCooldown(0); # MONITOR では変更してはいけない。。。
	}
}
