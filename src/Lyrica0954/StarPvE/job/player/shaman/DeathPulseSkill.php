<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\job\player\shaman;

use Lyrica0954\MagicParticle\CircleParticle;
use Lyrica0954\MagicParticle\effect\SaturatedLineworkEffect;
use Lyrica0954\MagicParticle\EmitterParticle;
use Lyrica0954\MagicParticle\ParticleOption;
use Lyrica0954\StarPvE\game\wave\MonsterData;
use Lyrica0954\StarPvE\job\AbilityStatus;
use Lyrica0954\StarPvE\job\ActionResult;
use Lyrica0954\StarPvE\job\Skill;
use Lyrica0954\StarPvE\translate\DescriptionTranslator;
use Lyrica0954\StarPvE\utils\EntityUtil;
use Lyrica0954\StarPvE\utils\PlayerUtil;
use Lyrica0954\StarPvE\utils\TaskUtil;
use Lyrica0954\StarPvE\utils\VectorUtil;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\math\Vector2;
use pocketmine\math\Vector3;
use pocketmine\scheduler\ClosureTask;

class DeathPulseSkill extends Skill {

	public function getCooltime(): int {
		return (120 * 20);
	}

	public function getName(): string {
		return "デスパルス";
	}

	public function getDescription(): string {
		$area = DescriptionTranslator::number($this->area, "m");
		$duration = DescriptionTranslator::second($this->duration);
		$damagePer = DescriptionTranslator::percentage($this->damage);
		$amount = DescriptionTranslator::number($this->amount, "回");
		$speedFake = new AbilityStatus(30);
		$speedFake->subtract($this->speed->getDiff());
		$period = DescriptionTranslator::second($speedFake);
		return
			sprintf('§b発動時:§f 自分から %1$s の地点にパルスを発生させ、§c2.5秒§f かけて自分のところまで移動する。
移動中パルスに触れた敵を %2$s スタンさせる。
パルスが移動し終えたら、
自分から %1$s 以内にいる敵全てに敵の最大体力 %3$s 分のダメージを与える攻撃を %4$s、%5$s ごとに行う。', $area, $duration, $damagePer, $amount, $period);
	}

	protected function init(): void {
		$this->damage = new AbilityStatus(0.08);
		$this->area = new AbilityStatus(10.0);
		$this->speed = new AbilityStatus(0);
		$this->duration = new AbilityStatus(120);
		$this->amount = new AbilityStatus(11);
	}

	protected function onActivate(): ActionResult {

		$preparePeriod = 2;
		$prepareLimit = 25;

		$area = $this->area->get();
		$prepareSize = $area;
		$std = new \stdClass;
		$std->size = $prepareSize;
		$step = ($area / $prepareLimit);

		TaskUtil::repeatingClosureLimit(function () use ($preparePeriod, $prepareLimit, $std, $step) {
			$par = (new CircleParticle($std->size, 5.5, 0, 360, 0.25));
			$pos = VectorUtil::keepAdd($this->player->getPosition(), 0, 0.9, 0);
			$par->sendToPlayers($this->player->getWorld()->getPlayers(), $pos, ParticleOption::spawnPacket("minecraft:obsidian_glow_dust_particle", ""));

			$std->size -= $step;

			$max = ($std->size + $step);
			$min = ($std->size - $step);
			foreach (EntityUtil::getWithin($pos, $min, $max) as $entity) {
				if (MonsterData::isMonster($entity)) {
					EntityUtil::immobile($entity, (int) $this->duration->get());
				}
			}
		}, $preparePeriod, $prepareLimit);
		TaskUtil::delayed(new ClosureTask(function () {
			TaskUtil::repeatingClosureLimit(function () {
				$area = $this->area->get();
				$par = (new CircleParticle($area, 5.5));
				$pos = VectorUtil::keepAdd($this->player->getPosition(), 0, 0.9, 0);
				$par->sendToPlayers(
					$this->player->getWorld()->getPlayers(),
					$pos,
					ParticleOption::spawnPacket(
						"minecraft:sparkler_emitter",
						""
					)
				);

				$base = (new Vector3($area, 0, $area))->divide(2);
				$emitter = new EmitterParticle($base->multiply(-1), $base, 20);
				$emitter->sendToPlayers(
					$this->player->getWorld()->getPlayers(),
					$pos,
					ParticleOption::spawnPacket(
						"starpve:rocket_sparkler_emitter",
						""
					)
				);

				$effect = (new SaturatedLineworkEffect($area, 3, 0, 12, 360, 0, 0));
				$effect->sendToPlayers($this->player->getWorld()->getPlayers(), $pos, ParticleOption::spawnPacket("minecraft:obsidian_glow_dust_particle", ""));

				PlayerUtil::broadcastSound($this->player, "mob.zombie.unfect", 1.0, 0.5);

				foreach (EntityUtil::getWithinRange($pos, $area) as $entity) {
					if (MonsterData::isMonster($entity)) {
						$damage = ($entity->getMaxHealth() * $this->damage->get());
						$source = new EntityDamageByEntityEvent($this->player, $entity, EntityDamageByEntityEvent::CAUSE_ENTITY_ATTACK, $damage, [], 0);
						$source->setAttackCooldown(0);
						$entity->attack($source);
					}
				}
			}, (30 - (int) min(29, $this->speed->get())), (int)$this->amount->get());
		}), ($preparePeriod * $prepareLimit));


		return ActionResult::SUCCEEDED();
	}
}
