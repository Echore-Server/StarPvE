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
		return (220 * 20);
	}

	public function getName(): string {
		return "フリーズアロー";
	}

	public function getDescription(): string {
		$area = DescriptionTranslator::number($this->area, "m");
		$duration = DescriptionTranslator::second($this->duration);
		$playerEffect = DescriptionTranslator::effectGroup($this->playerEffects);
		$areaEffect = DescriptionTranslator::effectGroup($this->areaEffects);
		$explodeEffect = DescriptionTranslator::effectGroup($this->explodeEffects);

		return
			sprintf('§b発動時:§f 周りの空間をゆがませて、安全地帯を作る有毒な矢を放つ。
矢はエンティティを貫通する。
地面に当たった場合 §b効果§f を発動する。

§b効果範囲:§f %1$s (§b効果範囲内§fにいるモンスターの数によって変わる)

§b効果(1):§f §b効果範囲§f 内の敵を %2$s §d中立化§f させる。
§b効果(2):§f §b効果範囲§f 内の味方に %3$s を与える。
§b効果(3):§f §b効果範囲§f 内の敵に %4$s を与える。

§b効果§f が発動してから %2$s 経つと、爆発し、
ステージ上の全ての敵に %5$s を与えてノックバックさせる。', $area, $duration, $playerEffect, $areaEffect, $explodeEffect);
	}

	protected function init(): void {
		$this->area = new AbilityStatus(4.0);
		$this->duration = new AbilityStatus(20 * 20);
		$this->areaEffects = new EffectGroup(
			new EffectInstance(VanillaEffects::SLOWNESS(), 6 * 20, 2, false),
			new EffectInstance(VanillaEffects::WEAKNESS(), 6 * 20, 0, false)
		);

		$this->explodeEffects = new EffectGroup(
			new EffectInstance(VanillaEffects::SLOWNESS(), 20 * 20, 1, true),
			new EffectInstance(VanillaEffects::WEAKNESS(), 20 * 20, 1, true)
		);

		$this->playerEffects = new EffectGroup(
			new EffectInstance(VanillaEffects::SPEED(), 1 * 20, 0, false)
		);
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
						$new->period = 10;
						$new->areaEffects = clone $this->areaEffects;
						$new->explodeEffects = clone $this->explodeEffects;
						$new->playerEffects = clone $this->playerEffects;
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
