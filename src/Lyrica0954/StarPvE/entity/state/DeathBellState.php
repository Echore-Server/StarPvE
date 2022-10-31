<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\entity\state;

use Lyrica0954\StarPvE\entity\EntityState;
use Lyrica0954\StarPvE\event\PlayerRespawnOnGameEvent;
use Lyrica0954\StarPvE\game\wave\MonsterData;
use Lyrica0954\StarPvE\utils\EntityUtil;
use Lyrica0954\StarPvE\utils\PlayerUtil;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\entity\Entity;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\Listener;
use pocketmine\player\Player;

class DeathBellState extends ListenerState {

	public function __construct(Player $entity) {
		parent::__construct($entity);
	}

	public function onRespawn(PlayerRespawnOnGameEvent $event): void {
		$entity = $event->getPlayer();
		if ($entity === $this->entity) {
			PlayerUtil::broadcastSound($entity, "block.bell.hit", 0.25, 0.75);
			foreach (EntityUtil::getWithinRange($entity->getPosition(), 10, $entity) as $target) {
				if (MonsterData::isMonster($target)) {
					$source = new EntityDamageByEntityEvent($entity, $target, EntityDamageEvent::CAUSE_MAGIC, $entity->getMaxHealth() * 2, [], 0.0);
					$source->setAttackCooldown(0);
					$target->attack($source);

					EntityUtil::immobile($target, (5 * 20));
				}
			}

			$effect = new EffectInstance(VanillaEffects::BLINDNESS(), 3 * 20, 0);
			$entity->getEffects()->add($effect);
		}
	}
}
