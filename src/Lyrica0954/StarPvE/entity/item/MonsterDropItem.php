<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\entity\item;

use Locale;
use Lyrica0954\StarPvE\utils\PlayerUtil;
use pocketmine\entity\Location;
use pocketmine\entity\object\ItemEntity;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\math\Vector2;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\player\Player;

class MonsterDropItem extends ItemEntity {

	private string $soundName = "";
	private float $soundPitch = 1.0;
	private float $soundVolume = 1.0;

	protected bool $pickup = false;

	/**
	 * @param Item $item
	 * 
	 * @return Item[]
	 */
	public static function split(Item $item): array {
		$items = [];
		$count = $item->getCount();
		for ($i = 1; $i <= $count; $i++) {
			$items[] = ItemFactory::getInstance()->get($item->getId());
		}

		return $items;
	}

	public function setSound(string $soundName, float $soundPitch, float $soundVolume) {
		$this->soundName = $soundName;
		$this->soundPitch = $soundPitch;
		$this->soundVolume = $soundVolume;
	}

	protected function onDispose(): void {
		$owning = $this->getOwningEntity();
		if ($owning instanceof Player) {
			if ($this->getWorld() === $owning->getWorld()) {
				if (!$this->pickup) {
					PlayerUtil::give($owning, $this->getItem());
				}
			}
		}
		parent::onDispose();
	}

	public function onCollideWithPlayer(Player $player): void {

		if ($this->pickupDelay > 0) {
			return;
		}

		if ($this->getOwningEntity() !== null && $this->getOwningEntity() !== $player) {
			return;
		}

		PlayerUtil::playSound($player, $this->soundName, $this->soundPitch, $this->soundVolume);

		$this->pickup = true;

		parent::onCollideWithPlayer($player);
	}

	protected function entityBaseTick(int $tickDiff = 1): bool {
		$hasUpdate = parent::entityBaseTick($tickDiff);

		if ($this->isOnFire()) {
			$this->setMotion(new Vector3(0, 0.5, 0));
		}

		return $hasUpdate;
	}

	public function isMergeable(ItemEntity $entity): bool {
		return false;
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
