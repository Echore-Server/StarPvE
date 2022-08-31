<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\job\player\shaman;

use Lyrica0954\MagicParticle\CircleParticle;
use Lyrica0954\MagicParticle\effect\SaturatedLineworkEffect;
use Lyrica0954\MagicParticle\EmitterParticle;
use Lyrica0954\MagicParticle\ParticleOption;
use Lyrica0954\StarPvE\game\wave\MonsterData;
use Lyrica0954\StarPvE\job\Ability;
use Lyrica0954\StarPvE\job\AbilityStatus;
use Lyrica0954\StarPvE\job\ActionResult;
use Lyrica0954\StarPvE\job\player\PlayerJob;
use Lyrica0954\StarPvE\StarPvE;
use Lyrica0954\StarPvE\translate\DescriptionTranslator;
use Lyrica0954\StarPvE\utils\EntityUtil;
use Lyrica0954\StarPvE\utils\ParticleUtil;
use Lyrica0954\StarPvE\utils\PlayerUtil;
use Lyrica0954\StarPvE\utils\TaskUtil;
use Lyrica0954\StarPvE\utils\VectorUtil;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\Listener;
use pocketmine\math\Vector2;
use pocketmine\scheduler\Task;
use pocketmine\Server;
use pocketmine\world\Position;

class DownPulseAbility extends Ability implements Listener {
	/**
	 * @var Entity[]
	 */
	public array $effected;

	public function getCooltime(): int {
		return (20 * 20);
	}

	public function getName(): string {
		return "ダウンパルス";
	}

	public function getDescription(): string {
		$area = DescriptionTranslator::number($this->area, "m");
		$damageAmp = DescriptionTranslator::percentage($this->percentage, false, -1.0);
		return
			sprintf('§b発動時:§f %1$s 以内の敵の受けるダメージを %2$s 上昇させる。', $area, $damageAmp);
	}

	public function getEffected(): array {
		return $this->effected;
	}

	protected function init(): void {
		$this->percentage = new AbilityStatus(1.3);
		$this->area = new AbilityStatus(4.0);
		$this->speed = new AbilityStatus(0.1);
	}

	protected function onActivate(): ActionResult {
		$task = new class($this) extends Task {

			private float $s;
			private Ability $ability;
			private Position $pos;
			private bool $effect;

			public function __construct(Ability $ability) {
				$this->ability = $ability;
				$this->pos = clone $ability->getPlayer()->getPosition();
				$this->s = 0;
				$this->effect = false;
			}

			public function onRun(): void {
				$player = $this->ability->getPlayer();
				$area = $this->ability->getArea()->get();
				$speed = $this->ability->getSpeed()->get();

				$this->s += ($speed);
				$offsetY = 5.0 - (5 * sqrt($this->s));
				if ($this->s >= (M_PI_2)) { #90
					$this->getHandler()->cancel();
				}

				if ($this->s >= (M_PI_4) && !$this->effect) { #45
					$this->effect = true;
					PlayerUtil::broadcastSound($this->pos, "mob.wither.break_block", 1.2, 0.6);
					foreach (EntityUtil::getWithinRangePlane(
						new Vector2(
							$this->pos->x,
							$this->pos->z
						),
						$this->pos->getWorld(),
						$area
					)
						as $entity) {
						if (MonsterData::isMonster($entity)) {
							$h = spl_object_hash($entity);
							if (!isset($this->ability->effected[$h])) {
								$this->ability->effected[$h] = $entity;

								$par = (new SaturatedLineworkEffect(3, 4, 0.6, 7));
								ParticleUtil::send($par, $entity->getWorld()->getPlayers(), VectorUtil::keepAdd($entity->getPosition(), 0, $entity->getEyeHeight(), 0), ParticleOption::spawnPacket("minecraft:obsidian_glow_dust_particle", ""));

								TaskUtil::reapeatingClosureCheck(function () use ($entity) {
									if ($entity->isAlive() && !$entity->isClosed()) {
										$min = EntityUtil::getCollisionMin($entity);
										$par = EmitterParticle::createEmitterForEntity($entity, 0.5, 1);
										ParticleUtil::send($par, $entity->getWorld()->getPlayers(), VectorUtil::insertWorld($min, $entity->getWorld()), ParticleOption::spawnPacket("minecraft:basic_crit_particle", ""));
									}
								}, 5, function () use ($entity) {
									$result = ($entity->isAlive() && !$entity->isClosed());
									if (!$result) {
										unset($this->ability->effected[spl_object_hash($entity)]);
									}
									return $result;
								});
							}
						}
					}
				}

				$par = (new CircleParticle($area, 6));
				$pos = clone $this->pos;
				$pos->y += $offsetY;
				ParticleUtil::send(
					$par,
					$player->getWorld()->getPlayers(),
					$pos,
					ParticleOption::spawnPacket("minecraft:falling_dust_concrete_powder_particle", "")
				);
			}
		};

		TaskUtil::repeating($task, 3);

		return ActionResult::SUCCEEDED();
	}

	public function onEntityDamage(EntityDamageEvent $event) {
		$entity = $event->getEntity();
		$h = spl_object_hash($entity);
		if (isset($this->effected[$h])) {
			EntityUtil::multiplyFinalDamage($event, $this->percentage->get());
		}
	}
}
