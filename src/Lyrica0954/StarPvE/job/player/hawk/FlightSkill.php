<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\job\player\hawk;

use Closure;
use Lyrica0954\MagicParticle\ParticleOption;
use Lyrica0954\MagicParticle\SingleParticle;
use Lyrica0954\MagicParticle\utils\MolangUtil;
use Lyrica0954\StarPvE\entity\EntityStateManager;
use Lyrica0954\StarPvE\entity\MemoryEntity;
use Lyrica0954\StarPvE\entity\state\FrozenState;
use Lyrica0954\StarPvE\game\wave\MonsterData;
use Lyrica0954\StarPvE\job\AbilityStatus;
use Lyrica0954\StarPvE\job\ActionResult;
use Lyrica0954\StarPvE\job\Skill;
use Lyrica0954\StarPvE\translate\DescriptionTranslator;
use Lyrica0954\StarPvE\utils\EntityUtil;
use Lyrica0954\StarPvE\utils\ParticleUtil;
use Lyrica0954\StarPvE\utils\RandomUtil;
use Lyrica0954\StarPvE\utils\SlowdownRunIds;
use Lyrica0954\StarPvE\utils\TaskUtil;
use pocketmine\color\Color;
use pocketmine\entity\Location;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\math\RayTraceResult;
use pocketmine\math\Vector2;
use pocketmine\math\Vector3;
use pocketmine\math\VoxelRayTrace;
use pocketmine\world\Position;
use stdClass;

class FlightSkill extends Skill {

	protected ?GrabSpell $targetSpell = null;

	public function getName(): string {
		return "飛行";
	}

	public function getDescription(): string {
		$duration = DescriptionTranslator::second($this->duration);
		$percentage = DescriptionTranslator::percentage($this->percentage);
		return sprintf('§n発動時:§f §d効果§f を発動する

§d効果時間:§f %1$s
§d効果: §f移動速度が %2$s 増加し、空を飛べるようになる。
アビリティの効果時間が §c50%%%%§f 増加する
§dわしづかみ§f のクールダウンが §c90%%%%§f 減少する
衝撃吸収ハートを §c5♡§f 獲得する
また飛行中敵を §c10秒§f §d凍結§f 状態にする雨を降らす。', $duration, $percentage);
	}

	protected function init(): void {
		$this->cooltime = new AbilityStatus(45 * 20);
		$this->duration = new AbilityStatus(6 * 20);
		$this->percentage = new AbilityStatus(1.0);
	}

	protected function onActivate(): ActionResult {
		if ($this->targetSpell === null) {
			foreach ($this->getJob()->getSpells() as $spell) {
				if ($spell instanceof GrabSpell) {
					$this->targetSpell = $spell;
					break;
				}
			}

			if ($this->targetSpell === null) return ActionResult::FAILED();
		}
		$duration = $this->duration->get();
		EntityUtil::slowdown($this->player, (int) $duration, 1.0 + $this->percentage->get(), SlowdownRunIds::get($this::class));
		EntityUtil::absorption($this->player, 10, (int) $duration);

		$this->job->getAbility()->getDuration()->multiply(1.5);
		$this->targetSpell->getCooltime()->multiply(0.1);

		$this->player->setAllowFlight(true);

		if (!$this->player->isOnGround()) {
			$this->player->setFlying(true);
		}

		$std = new \stdClass;
		$std->tick = 0;

		$createRain = function (Location $loc): MemoryEntity {
			$entity = new MemoryEntity($loc, null, 0.1);
			$entity->setKeepMovement(true);

			$store = new stdClass;
			$store->lastPos = $loc;

			$entity->addTickHook(function (MemoryEntity $e) use ($store): void {
				$start = $store->lastPos;
				$end = $e->getPosition();

				$blockHit = null;
				$hitResult = null;

				ParticleUtil::send(
					new SingleParticle,
					$e->getWorld()->getPlayers(),
					$end,
					ParticleOption::spawnPacket("minecraft:balloon_gas_particle")
				);

				if ($end->distanceSquared($start) > 0.01) {
					foreach (VoxelRayTrace::betweenPoints($start, $end) as $vector3) {
						$block = $e->getWorld()->getBlockAt($vector3->x, $vector3->y, $vector3->z);

						$blockHitResult = $block->calculateIntercept($start, $end);
						if ($blockHitResult !== null) {
							$end = $blockHitResult->hitVector;
							$blockHit = $block;
							$hitResult = $blockHitResult;
							break;
						}
					}
				}

				/**
				 * @var RayTraceResult|null $hitResult
				 */

				if ($hitResult instanceof RayTraceResult) {
					$molang = ParticleUtil::circleMolang(10 / 20, 50, 1.5, new Color(0, 204, 255, (int) (0.5 * 255)), new Vector3(0, 0, 0));
					ParticleUtil::send(
						new SingleParticle,
						$e->getWorld()->getPlayers(),
						Position::fromObject($hitResult->getHitVector(), $e->getWorld()),
						ParticleOption::spawnPacket("starpve:circle", MolangUtil::encode($molang))
					);

					foreach (EntityUtil::getWithinRange(Position::fromObject($hitResult->getHitVector(), $e->getWorld()), 1.5) as $target) {
						if (MonsterData::isMonster($target)) {
							EntityStateManager::startWithDuration(new FrozenState($target, 2.0), 10 * 20);
							EntityUtil::slowdown($target, 10 * 20, 0.4, SlowdownRunIds::get($this::class));

							$source = new EntityDamageByEntityEvent($this->player, $target, EntityDamageEvent::CAUSE_MAGIC, 1, [], 0);
							$target->attack($source);
						}
					}

					$e->close();
				}

				$store->lastPos = $end;
			});

			return $entity;
		};

		TaskUtil::repeatingClosureFailure(function (Closure $fail) use ($duration, $std, $createRain): void {
			$std->tick++;

			if ($std->tick >= $duration) {
				$this->player->setFlying(false);
				$this->player->setAllowFlight(false);
				$this->job->getAbility()->getDuration()->divide(1.5);
				$this->targetSpell->getCooltime()->divide(0.1);
				$fail();
				return;
			}

			if (!$this->player->isOnGround()) {
				$vec = $this->player->getPosition()->add(RandomUtil::rand_float(-5, 5), RandomUtil::rand_float(0, 3), RandomUtil::rand_float(-5, 5));
				$createRain(Location::fromObject($vec, $this->player->getWorld()));
			}
		}, 1);

		return ActionResult::SUCCEEDED();
	}
}
