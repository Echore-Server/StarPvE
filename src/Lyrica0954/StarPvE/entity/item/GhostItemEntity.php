<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\entity\item;

use pocketmine\entity\object\ItemEntity;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\player\Player;

class GhostItemEntity extends ItemEntity {

	public function isMergeable(ItemEntity $entity): bool {
		return false;
	}

	public function onCollideWithPlayer(Player $player): void {
		# NOOPOOP
	}

	public function isFireProof(): bool {
		return true;
	}

	public function attack(EntityDamageEvent $source): void {
		if (
			$source->getCause() === EntityDamageEvent::CAUSE_LAVA ||
			$source->getCause() === EntityDamageEvent::CAUSE_FIRE_TICK ||
			$source->getCause() === EntityDamageEvent::CAUSE_FIRE
		) {
			return;
		}

		parent::attack($source);
	}
}
