<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\job\player\engineer;

use Lyrica0954\StarPvE\job\Ability;
use Lyrica0954\StarPvE\job\AbilityStatus;
use Lyrica0954\StarPvE\job\ActionResult;
use Lyrica0954\StarPvE\job\player\engineer\entity\GravityBall;
use Lyrica0954\StarPvE\job\player\engineer\entity\ShieldBall;
use Lyrica0954\StarPvE\job\player\engineer\entity\ToxicBin;
use Lyrica0954\StarPvE\job\Skill;
use Lyrica0954\StarPvE\job\skill\ThrowEntitySkillBase;
use Lyrica0954\StarPvE\translate\DescriptionTranslator;
use pocketmine\entity\Entity;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;

class ThrowToxicBinSkill extends ThrowEntitySkillBase {

	public function getName(): string {
		return "ライトニングクラウド";
	}

	public function getDescription(): string {
		$area = DescriptionTranslator::number($this->area, "m");
		$damage = DescriptionTranslator::health($this->damage);
		$duration = DescriptionTranslator::second($this->duration);
		# %%%% は sprintfで %% と認識されるが、クライアント側でまたフォーマットしてるらしく、結局 %単体になる。
		return
			sprintf('§b発動時:§f 視線の先に雷の雲が入った瓶を投げる。
§b効果範囲§f: %1$s
§b効果§f: 範囲内の敵に %2$s ダメージを与える雷の雲を %3$s 間展開する。
敵が §d帯電 §f状態の時、 §c2§f 倍のダメージを与える。
§b効果§f: 範囲内の敵の移動速度を §c15%%%%§f 低下させる。', $area, $damage, $duration);
	}

	protected function init(): void {
		$this->duration = new AbilityStatus(10 * 20);
		$this->area = new AbilityStatus(6.0);
		$this->damage = new AbilityStatus(1.8);
		$this->speed = new AbilityStatus(0.9);
		$this->cooltime = new AbilityStatus(33 * 20);
	}

	protected function getEntity(): Entity {
		$loc = $this->player->getLocation();
		$loc->y += $this->player->getEyeHeight();
		$entity = new ToxicBin($loc, $this->player);
		$entity->duration = (int) $this->duration->get();
		$entity->areaDamage = $this->damage->get();
		$entity->radius = $this->area->get();
		$entity->spawnToAll();

		return $entity;
	}
}
