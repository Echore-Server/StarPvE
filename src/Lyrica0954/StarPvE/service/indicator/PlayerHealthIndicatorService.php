<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\service\indicator;

use Lyrica0954\StarPvE\service\ListenerService;
use Lyrica0954\StarPvE\utils\TaskUtil;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityEffectEvent;
use pocketmine\event\entity\EntityRegainHealthEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\player\Player;
use pocketmine\scheduler\ClosureTask;

class PlayerHealthIndicatorService extends ListenerService {

	public function onJoin(PlayerJoinEvent $event) {
		$player = $event->getPlayer();
		$player->setScoreTag($this->getTag($player));
	}

	protected function getTag(Player $player, float $healthDiff = 0.0): string {
		$health = $player->getHealth();
		$maxHealth = $player->getMaxHealth();
		$health = min($health + $healthDiff, $maxHealth);
		$absorption = $player->getAbsorption();
		$health += $absorption;
		return ($absorption > 0.0 ? "§e" : "§c") . round($health, 1) . "§f / §8" . $maxHealth . " §fHP";
	}

	/**
	 * @param EntityRegainHealthEvent $event
	 * 
	 * @return void
	 * 
	 * @priority MONITOR
	 */
	public function onRegain(EntityRegainHealthEvent $event): void {
		$heal = $event->getAmount();
		$entity = $event->getEntity();
		if ($entity instanceof Player) {
			$entity->setScoreTag($this->getTag($entity, $heal));
		}
	}

	/**
	 * @param EntityDamageEvent $event
	 * 
	 * @return void
	 * 
	 * @priority MONITOR
	 */
	public function onDamage(EntityDamageEvent $event): void {
		$damage = $event->getFinalDamage();
		$entity = $event->getEntity();
		if ($entity instanceof Player) {
			$entity->setScoreTag($this->getTag($entity, -$damage));
		}
	}

	/**
	 * @param EntityEffectEvent $event
	 * 
	 * @return void
	 * 
	 * @priority MONITOR
	 */
	public function onEffect(EntityEffectEvent $event): void {
		$entity = $event->getEntity();
		if ($entity instanceof Player) {
			TaskUtil::delayed(new ClosureTask(function () use ($entity) {
				$entity->setScoreTag($this->getTag($entity));
			}), 1);
		}
	}
}
