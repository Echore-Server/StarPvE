<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\job\player\castle;

use Lyrica0954\StarPvE\job\Ability;
use Lyrica0954\StarPvE\job\ability\ThrowEntityAbilityBase;
use Lyrica0954\StarPvE\job\AbilityStatus;
use Lyrica0954\StarPvE\job\ActionResult;
use Lyrica0954\StarPvE\job\player\castle\entity\TrapDevice;
use Lyrica0954\StarPvE\job\player\engineer\entity\GravityBall;
use Lyrica0954\StarPvE\translate\DescriptionTranslator;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\entity\Entity;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;


class ThrowTrapAbility extends ThrowEntityAbilityBase {

	public function getCooltime(): int {
		return (27 * 20);
	}

	public function getName(): string {
		return "トラップ";
	}

	public function getDescription(): string {
		$area = DescriptionTranslator::number($this->area, "m");
		$damage = DescriptionTranslator::health($this->damage);
		$amount = DescriptionTranslator::number($this->amount, "体");
		$duration = DescriptionTranslator::second($this->duration);
		return
			sprintf('§b発動時: §f特殊な装置を投げる。

装置が地面につくと、§b効果§f を発動させる。

§b効果: §f装置から敵が %1$s 以内に §c3秒§f 以上とどまると、
その敵に %2$s のダメージを与えて、§c2秒§f スタンさせる。
一度トラップされた敵はトラップしない。

効果は、効果が発動してから %4$s 経過するか、敵 %3$s 以上をトラップすることで消滅する。', $area, $damage, $amount, $duration);
	}

	protected function init(): void {
		$this->damage = new AbilityStatus(20.0);
		$this->speed = new AbilityStatus(0.9);
		$this->area = new AbilityStatus(4.0);
		$this->amount = new AbilityStatus(6.0);
		$this->duration = new AbilityStatus(11 * 20);
	}

	protected function getEntity(): Entity {
		$item = ItemFactory::getInstance()->get(ItemIds::LEAD);
		$loc = $this->player->getLocation();
		$loc->y += $this->player->getEyeHeight();
		$entity = new TrapDevice($loc, $item);
		$entity->damage = $this->damage->get();
		$entity->duration = (int) $this->duration->get();
		$entity->area = $this->area->get();
		$entity->amount = (int) $this->amount->get();

		return $entity;
	}
}
