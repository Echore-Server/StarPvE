<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\job\player\engineer;

use Lyrica0954\StarPvE\job\AbilitySpell;
use Lyrica0954\StarPvE\job\AbilityStatus;
use Lyrica0954\StarPvE\job\ActionResult;
use Lyrica0954\StarPvE\job\player\engineer\entity\GrenadeEntity;
use Lyrica0954\StarPvE\translate\DescriptionTranslator;
use pocketmine\entity\Location;
use pocketmine\item\Item;
use pocketmine\item\VanillaItems;

class GrenadeSpell extends AbilitySpell {

	public function getName(): string {
		return "手りゅう弾";
	}

	public function getActivateItem(): Item {
		return VanillaItems::FERMENTED_SPIDER_EYE()->setCustomName("§r§l§c{$this->getName()}");
	}

	protected function init(): void {
		$this->area = new AbilityStatus(5.0);
		$this->damage = new AbilityStatus(3.5);
		$this->cooltime = new AbilityStatus(12 * 20);
	}

	public function getDescription(): string {
		$area = DescriptionTranslator::number($this->area, "m");
		$damage = DescriptionTranslator::health($this->damage);
		return sprintf('§b発動時: §fヒットすると %1$s 内の敵に %2$s ダメージと §c0.1秒§f のスタンを与える手りゅう弾を投擲する。', $area, $damage);
	}

	protected function onActivate(): ActionResult {
		$loc = Location::fromObject($this->player->getEyePos(), $this->player->getWorld());
		$entity = new GrenadeEntity($loc, $this->player);
		$entity->areaDamage = $this->damage->get();
		$entity->range = $this->area->get();
		$entity->setMotion($this->player->getDirectionVector()->multiply(0.9));
		$entity->spawnToAll();

		return ActionResult::SUCCEEDED();
	}
}
