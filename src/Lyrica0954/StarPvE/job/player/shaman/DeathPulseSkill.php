<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\job\player\shaman;

use Lyrica0954\MagicParticle\CircleParticle;
use Lyrica0954\MagicParticle\effect\SaturatedLineworkEffect;
use Lyrica0954\MagicParticle\EmitterParticle;
use Lyrica0954\MagicParticle\ParticleOption;
use Lyrica0954\MagicParticle\SingleParticle;
use Lyrica0954\StarPvE\game\wave\MonsterData;
use Lyrica0954\StarPvE\job\AbilityStatus;
use Lyrica0954\StarPvE\job\ActionResult;
use Lyrica0954\StarPvE\job\Skill;
use Lyrica0954\StarPvE\translate\DescriptionTranslator;
use Lyrica0954\StarPvE\utils\EntityUtil;
use Lyrica0954\StarPvE\utils\ParticleUtil;
use Lyrica0954\StarPvE\utils\PlayerUtil;
use Lyrica0954\StarPvE\utils\TaskUtil;
use Lyrica0954\StarPvE\utils\VectorUtil;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\math\Vector2;
use pocketmine\math\Vector3;
use pocketmine\scheduler\ClosureTask;

class DeathPulseSkill extends Skill {

	public function getName(): string {
		return "デスパルス";
	}

	public function getDescription(): string {
		$area = DescriptionTranslator::number($this->area, "m");
		$duration = DescriptionTranslator::second($this->duration);
		$damagePer = DescriptionTranslator::percentage($this->percentage);
		$amount = DescriptionTranslator::number($this->amount, "回");
		$speedFake = new AbilityStatus(30);
		$speedFake->subtract($this->speed->getDiff());
		$period = DescriptionTranslator::second($speedFake);
		return
			sprintf('§b発動時:§f 周囲 %1$s の地点に、触れた敵を %2$s スタンさせながら収縮するパルスを発生させ、
収縮が終わると自分から %1$s 以内にいる敵全てに敵の最大体力 %3$s 分のダメージを与える攻撃を %4$s、%5$s ごとに行う。', $area, $duration, $damagePer, $amount, $period);
	}

	protected function init(): void {
		$this->percentage = new AbilityStatus(0.05);
		$this->area = new AbilityStatus(10.0);
		$this->speed = new AbilityStatus(0);
		$this->duration = new AbilityStatus(120);
		$this->amount = new AbilityStatus(10);
		$this->cooltime = new AbilityStatus(120 * 20);
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
			ParticleUtil::send($par, $this->player->getWorld()->getPlayers(), $pos, ParticleOption::spawnPacket("minecraft:obsidian_glow_dust_particle", ""));

			$std->size -= $step;

			$max = ($std->size + $step);
			$min = ($std->size - $step);
			foreach (EntityUtil::getWithinPlane($pos, $min, $max) as $entity) {
				if (MonsterData::isMonster($entity)) {
					EntityUtil::immobile($entity, (int) $this->duration->get());
				}
			}
		}, $preparePeriod, $prepareLimit);
		TaskUtil::delayed(new ClosureTask(function () {
			TaskUtil::repeatingClosureLimit(function () {
				$area = $this->area->get();
				$par = (new CircleParticle($area, 9));
				$pos = VectorUtil::keepAdd($this->player->getPosition(), 0, 0.9, 0);
				ParticleUtil::send(
					$par,
					$this->player->getWorld()->getPlayers(),
					$pos,
					ParticleOption::spawnPacket(
						"minecraft:sparkler_emitter",
						""
					)
				);

				ParticleUtil::send(
					new SingleParticle,
					$this->player->getWorld()->getPlayers(),
					$pos,
					ParticleOption::spawnPacket(
						"starpve:shaman_sparkler",
						""
					)
				);

				$effect = (new SaturatedLineworkEffect($area, 2, 0, 8, 360, 0, 0));
				ParticleUtil::send($effect, $this->player->getWorld()->getPlayers(), $pos, ParticleOption::spawnPacket("minecraft:obsidian_glow_dust_particle", ""));

				PlayerUtil::broadcastSound($this->player, "mob.zombie.unfect", 1.0, 0.5);

				foreach (EntityUtil::getWithinRange($pos, $area) as $entity) {
					if (MonsterData::isMonster($entity)) {
						$damage = ($entity->getMaxHealth() * $this->percentage->get());
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
