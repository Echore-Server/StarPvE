<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\job\player\castle;

use Lyrica0954\StarPvE\job\Ability;
use Lyrica0954\StarPvE\job\ability\ThrowEntityAbilityBase;
use Lyrica0954\StarPvE\job\AbilityStatus;
use Lyrica0954\StarPvE\job\ActionResult;
use Lyrica0954\StarPvE\job\player\castle\entity\TrapDevice;
use Lyrica0954\StarPvE\job\player\castle\entity\VoidDevice;
use Lyrica0954\StarPvE\job\player\engineer\entity\GravityBall;
use Lyrica0954\StarPvE\job\skill\ThrowEntitySkillBase;
use Lyrica0954\StarPvE\translate\DescriptionTranslator;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\entity\Entity;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;


class ThrowVoidSkill extends ThrowEntitySkillBase {

	public function getName(): string {
		return "ヴォイド";
	}

	public function getDescription(): string {
		$area = DescriptionTranslator::number($this->area, "m");
		$percentage = DescriptionTranslator::percentage($this->percentage, true);
		$duration = DescriptionTranslator::second($this->duration);
		return
			sprintf('§b発動時: §f特殊な装置を投げる。

装置は地面につくと、 §b効果§f を発動させる。

効果: 装置から %1$s 以内の敵が受けるダメージを %2$s 増加させる。
装置から %1$s 以内の敵が通常攻撃(防具貫通などは含まれない)のダメージを受けると、
そのダメージの半分を防具貫通として装置から §c3.5m§f 以内の敵に与える。

効果は、効果が発動してから %3$s 経過で消滅する。', $area, $percentage, $duration);
	}

	protected function init(): void {
		$this->speed = new AbilityStatus(0.9);
		$this->area = new AbilityStatus(8.0);
		$this->duration = new AbilityStatus(33 * 20);
		$this->percentage = new AbilityStatus(1.5);
		$this->cooltime = new AbilityStatus(100 * 20);
	}

	protected function getEntity(): Entity {
		$item = ItemFactory::getInstance()->get(ItemIds::OBSIDIAN);
		$loc = $this->player->getLocation();
		$loc->y += $this->player->getEyeHeight();
		$entity = new VoidDevice($loc, $item);
		$entity->duration = (int) $this->duration->get();
		$entity->area = $this->area->get();
		$entity->percentage = $this->percentage->get();
		$entity->damageArea = 2.0;

		return $entity;
	}
}
