<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\job\player\archer;

use Lyrica0954\StarPvE\job\AbilityStatus;
use Lyrica0954\StarPvE\job\ActionResult;
use Lyrica0954\StarPvE\job\player\archer\entity\FreezeArrow;
use Lyrica0954\StarPvE\job\Skill;
use Lyrica0954\StarPvE\translate\DescriptionTranslator;
use Lyrica0954\StarPvE\utils\EffectGroup;
use Lyrica0954\StarPvE\utils\Messanger;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\entity\projectile\Arrow;
use pocketmine\event\entity\EntityShootBowEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerItemUseEvent;
use pocketmine\item\Bow;
use pocketmine\item\VanillaItems;
use pocketmine\player\Player;

class FreezeArrowSkill extends Skill implements Listener {

	/**
	 * @var EffectGroup
	 */
	protected EffectGroup $areaEffects;

	/**
	 * @var EffectGroup
	 */
	protected EffectGroup $playerEffects;

	/**
	 * @var EffectGroup
	 */
	protected EffectGroup $explodeEffects;

	public function getCooltime(): int {
		return (90 * 20);
	}

	public function getName(): string {
		return "フリーズアロー";
	}

	public function getDescription(): string {
		$area = DescriptionTranslator::number($this->area, "m");
		$duration = DescriptionTranslator::second($this->duration);

		return
			sprintf('§b発動時:§f 周りの空間をゆがませて、敵を捕らえるエリアを生成する
矢はエンティティを貫通する。
地面に当たった場合 §b効果§f を発動する。

§b効果§f: %1$s 内の敵を範囲内から出れなくさせる。
§b効果時間§fが終わるにつれて効果範囲は狭くなっていく。

§b効果§f が発動してから %2$s 経つと消滅する。', $area, $duration);
	}

	protected function init(): void {
		$this->area = new AbilityStatus(10.0);
		$this->duration = new AbilityStatus(16 * 20);
	}

	public function onShoot(EntityShootBowEvent $event) {
		$projectile = $event->getProjectile();
		$entity = $event->getEntity();
		if ($projectile instanceof Arrow) {
			if ($entity === $this->player && $entity instanceof Player) {
				if ($event->getForce() == 3.0 && $entity->isSneaking()) {
					$result = $this->activate();
					if ($result->isSucceeded()) {
						$new = new FreezeArrow($projectile->getLocation(), $projectile->getOwningEntity(), $projectile->isCritical(), $projectile->saveNBT());
						$new->duration = (int) $this->duration->get();
						$new->area = $this->area->get();
						$new->period = 1;
						$new->setOwningEntity($this->player);
						$event->setProjectile($new);
					} else {
						$event->cancel();
					}
				}
			}
		}
	}

	public function onItemUse(PlayerItemUseEvent $event) {
		$item = $event->getItem();
		$player = $event->getPlayer();

		if ($player === $this->player) {
			if ($item instanceof Bow) {
				if ($player->isSneaking()) {
					Messanger::tooltip($player, "§c§l誤発動に注意: このまま(スニークしながら)最大チャージで発射するとスキルを使用します！");
				}
			}
		}
	}

	protected function onActivate(): ActionResult {
		return ActionResult::SUCCEEDED_SILENT();
	}
}
