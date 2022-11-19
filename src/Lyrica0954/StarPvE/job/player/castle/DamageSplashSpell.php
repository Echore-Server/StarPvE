<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\job\player\castle;

use Lyrica0954\StarPvE\job\AbilitySpell;
use Lyrica0954\StarPvE\job\AbilityStatus;
use Lyrica0954\StarPvE\job\ActionResult;
use Lyrica0954\StarPvE\job\player\castle\entity\DamageSplashPotion;
use Lyrica0954\StarPvE\translate\DescriptionTranslator;
use Lyrica0954\StarPvE\utils\VectorUtil;
use pocketmine\entity\Location;
use pocketmine\entity\projectile\SplashPotion;
use pocketmine\item\Item;
use pocketmine\item\VanillaItems;

class DamageSplashSpell extends AbilitySpell {

	public function getName(): string {
		return "ダメージスプラッシュ";
	}

	public function getDescription(): string {
		$amount = DescriptionTranslator::number($this->amount, "発");
		$damage = DescriptionTranslator::health($this->damage);
		return sprintf('§b発動時:§f %1$s の %2$s ダメージを与えるポーションを視線の方向に放つ。', $amount, $damage);
	}

	public function getActivateItem(): Item {
		return VanillaItems::BLAZE_POWDER()->setCustomName("§r§l§1{$this->getName()}");
	}

	protected function init(): void {
		$this->cooltime = new AbilityStatus(24 * 20);
		$this->amount = new AbilityStatus(14);
		$this->damage = new AbilityStatus(2.0);
	}

	protected function onActivate(): ActionResult {
		$amount = $this->amount->get();
		$baseYaw = $this->player->getLocation()->getYaw();
		$basePitch = $this->player->getLocation()->getPitch();
		$loc = Location::fromObject($this->player->getEyePos(), $this->player->getWorld());
		for ($i = 0; $i < $amount; $i++) {
			$yaw = $baseYaw + ((lcg_value() - 0.5) * (14 * 2));
			$pitch = $basePitch + ((lcg_value() - 0.5) * (14 * 2));

			$v = VectorUtil::getDirectionVector($yaw, $pitch);

			$entity = new DamageSplashPotion($loc, $this->player);
			$entity->areaDamage = $this->damage->get();
			$entity->setMotion($v->multiply(0.9));

			$entity->spawnToAll();
		}

		return ActionResult::SUCCEEDED();
	}
}
