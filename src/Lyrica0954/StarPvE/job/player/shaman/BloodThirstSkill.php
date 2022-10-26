<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\job\player\shaman;

use Lyrica0954\MagicParticle\ParticleOption;
use Lyrica0954\MagicParticle\SingleParticle;
use Lyrica0954\StarPvE\job\AbilityStatus;
use Lyrica0954\StarPvE\job\ActionResult;
use Lyrica0954\StarPvE\job\player\shaman\entity\SpiritEntity;
use Lyrica0954\StarPvE\job\Skill;
use Lyrica0954\StarPvE\job\StatusTranslate;
use Lyrica0954\StarPvE\translate\DescriptionTranslator;
use Lyrica0954\StarPvE\utils\EntityUtil;
use Lyrica0954\StarPvE\utils\ParticleUtil;
use Lyrica0954\StarPvE\utils\PlayerUtil;
use pocketmine\entity\Entity;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityRegainHealthEvent;

class BloodThirstSkill extends Skill {

	/**
	 * @var AbilityStatus
	 */
	protected AbilityStatus $reachPercentage;

	/**
	 * @var AbilityStatus
	 */
	protected AbilityStatus $damagePercentage;

	public function getName(): string {
		return "ブラッドサースト";
	}

	public function getDescription(): string {
		$area = DescriptionTranslator::number($this->area, "m");
		$percentage = DescriptionTranslator::percentage($this->percentage);
		$reachPercentage = DescriptionTranslator::percentage($this->reachPercentage);
		$damagePercentage = DescriptionTranslator::percentage($this->damagePercentage);
		return sprintf('§b発動時: §c7♡§f のダメージを受ける。
%1$s 以内の幽体の体力を最大体力の §c15%%%%§f 分回復し、最大体力を %2$s 、 攻撃範囲(リーチ)を %3$s 、 攻撃力を %4$s 増加させる。', $area, $percentage, $reachPercentage, $damagePercentage);
	}

	protected function init(): void {
		$this->percentage = new AbilityStatus(0.22);
		$this->reachPercentage = new AbilityStatus(0.12);
		$this->damagePercentage = new AbilityStatus(0.07);
		$this->area = new AbilityStatus(9.0);

		$this->cooltime = new AbilityStatus(28.0 * 20);
	}

	public function getStatusList(int $status): ?array {
		$list = parent::getStatusList($status);
		if (is_null($list)) {
			return null;
		}

		if ($status === StatusTranslate::STATUS_PERCENTAGE) {
			$list[] = $this->reachPercentage;
			$list[] = $this->damagePercentage;
		}

		return $list;
	}

	protected function onActivate(): ActionResult {
		$source = new EntityDamageEvent($this->player, EntityDamageEvent::CAUSE_DROWNING, 14.0);
		$this->player->attack($source);
		$perc = 1.0 + $this->percentage->get();
		$rperc = 1.0 + $this->reachPercentage->get();
		$dperc = 1.0 + $this->damagePercentage->get();
		foreach (EntityUtil::getWithinRange($this->player->getPosition(), $this->area->get()) as $entity) {
			if ($entity instanceof SpiritEntity) {

				$entity->setMaxHealth((int) ($entity->getMaxHealth() * $perc));
				$entity->setAttackRange($entity->getAttackRange() * $rperc);
				$entity->setAttackDamage($entity->getAttackDamage() * $dperc);
				$healSource = new EntityRegainHealthEvent($entity, $entity->getMaxHealth() * 0.15, EntityRegainHealthEvent::CAUSE_MAGIC);
				$entity->heal($healSource);
				ParticleUtil::send(
					new SingleParticle,
					$entity->getWorld()->getPlayers(),
					$entity->getPosition(),
					ParticleOption::spawnPacket("starpve:blood")
				);
			}
		}
		PlayerUtil::broadcastSound($this->player, "shriek.sculk_shrieker", 3.0, 0.2);
		PlayerUtil::broadcastSound($this->player, "spread.sculk", 0.36, 1.0);
		return ActionResult::SUCCEEDED();
	}
}
