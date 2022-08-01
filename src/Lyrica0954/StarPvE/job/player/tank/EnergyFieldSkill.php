<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\job\player\tank;

use Lyrica0954\MagicParticle\LineParticle;
use Lyrica0954\MagicParticle\ParticleOption;
use Lyrica0954\MagicParticle\SphereParticle;
use Lyrica0954\StarPvE\game\wave\MonsterData;
use Lyrica0954\StarPvE\job\AbilityStatus;
use Lyrica0954\StarPvE\job\ActionResult;
use Lyrica0954\StarPvE\job\LineOption;
use Lyrica0954\StarPvE\job\player\engineer\entity\ShieldBall;
use Lyrica0954\StarPvE\job\Skill;
use Lyrica0954\StarPvE\translate\DescriptionTranslator;
use Lyrica0954\StarPvE\utils\EntityUtil;
use Lyrica0954\StarPvE\utils\ParticleUtil;
use Lyrica0954\StarPvE\utils\PlayerUtil;
use Lyrica0954\StarPvE\utils\TaskUtil;
use Lyrica0954\StarPvE\utils\VectorUtil;
use pocketmine\entity\Entity;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\Listener;
use pocketmine\player\Player;

class EnergyFieldSkill extends Skill implements Listener {

	const MODIFIER_ABILITY = 99999;

	protected int $tick;

	public function getCooltime(): int {
		return (50 * 20);
	}

	public function getName(): string {
		return "エネルギーフィールド";
	}

	protected function init(): void {
		$this->area = new AbilityStatus(6.5);
		$this->duration = new AbilityStatus(5);
		$this->tick = 0;
	}

	public function getDescription(): string {
		$area = DescriptionTranslator::number($this->area, "m");
		$duration = DescriptionTranslator::second($this->duration);
		return sprintf(
			'%2$s に §c1§f エネルギーを消費して、自分から %1$s 以内の味方を守るフィールドを展開する。
範囲内の味方が攻撃を受けた場合、ダメージ量エネルギーを消費して、その攻撃を無効化する。
自分自身はこの対象に含まれない。
エネルギーフィールド内に §dEngineer§f のシールドボールがある場合、そのパワーを §c1秒§f に §c2%%%%§f 回復させる。
エネルギーが §c0§f になると、フィールドは消滅する。

さらに、発動中はアビリティが特殊アビリティに変化する。',
			$area,
			$duration
		);
	}

	public function activate(): ActionResult {
		if (!$this->closed) {
			if (!$this->cooltimeHandler->isActive()) {
				if (!$this->active) {
					if ($this->getCooltime() > 0) {
						$this->cooltimeHandler->start($this->getCooltime());
					}
					$result = $this->onActivate();

					if ($result->isMiss()) {
						$this->cooltimeHandler->stop();
					}

					return $result;
				} else {
					if ($this->isActive()) {
						$this->active = false;
						$this->tick = 0;
						$this->cooltimeHandler->start($this->getCooltime());
						foreach ($this->tasks as $taskHandler) {
							if (!$taskHandler->isCancelled()) {
								$taskHandler->cancel();
							}
						}

						$this->player->sendMessage("§aスキルをキャンセルしました");

						$job = $this->getJob();
						if ($job instanceof Tank) {
							$job->setAbility(new RegrowthAbility($job));
						}
					}
					return ActionResult::MISS();
				}
			} else {
				return ActionResult::FAILED_BY_COOLTIME();
			}
		} else {
			throw new \Exception("cannot activate closed ability");
		}
	}


	protected function onActivate(): ActionResult {
		$job = $this->getJob();
		if ($job instanceof Tank) {
			if ($job->getEnergy() <= 0) {
				$job->getActionListManager()->push(new LineOption("§cエネルギーが足りません！"));
				return ActionResult::MISS();
			} else {
				$job->setAbility(new EnergyPulseAbility($job));
			}
		}
		$this->active = true;


		$task = TaskUtil::reapeatingClosureCheck(function () {
			if (!$this->player->isConnected()) {
				foreach ($this->tasks as $taskHandler) {
					if (!$taskHandler->isCancelled()) {
						$taskHandler->cancel();
					}
				}
				return;
			}
			if ($this->tick % 30 == 0) {
				ParticleUtil::send(
					new SphereParticle($this->area->get(), 10, 10, 360, -90, 0),
					$this->player->getWorld()->getPlayers(),
					$this->player->getPosition(),
					ParticleOption::spawnPacket("starpve:soft_yellow_gas", "")
				);
			}

			$job = $this->getJob();
			if ($job instanceof Tank) {
				$job->addEnergy(- (1 / $this->duration->get()));
				if ($job->getEnergy() >= 300) {
					$this->area->setModifier(self::MODIFIER_ABILITY, ($this->area->getOriginal() / 2));
				} else {
					$this->area->setModifier(self::MODIFIER_ABILITY, 0);
				}


				if ($job->getEnergy() >= 200 && $this->tick % 10 == 0) {
					$entities = array_filter(
						EntityUtil::getWithinRange($this->player->getPosition(), $this->area->get()),
						function (Entity $item): bool {
							return MonsterData::isMonster($item);
						}
					);
					if (count($entities) > 0) {
						$entity = $entities[array_rand($entities)];

						ParticleUtil::send(
							new LineParticle(VectorUtil::keepAdd($this->player->getPosition(), 0, 1.0, 0), 2),
							$this->player->getWorld()->getPlayers(),
							VectorUtil::keepAdd($entity->getPosition(), 0, $entity->getEyeHeight(), 0),
							ParticleOption::spawnPacket("starpve:soft_yellow_gas", "")
						);

						$source = new EntityDamageEvent($entity, EntityDamageEvent::CAUSE_MAGIC, 2);
						$source->setAttackCooldown(1);
						$entity->attack($source);

						PlayerUtil::broadcastSound($entity, "firework.twinkle", 0.8, 0.6);
					}
				}
			}

			if ($this->tick % 20 == 0) {
				$this->player->setHealth(min($this->player->getHealth() + 1, $this->player->getMaxHealth()));
				foreach (EntityUtil::getWithinRange($this->player->getPosition(), $this->area->get()) as $entity) {
					if ($entity instanceof ShieldBall) {
						$entity->setPower($entity->getPower() + 2);
					}
				}
			}
			$this->tick++;
		}, 1, function () {
			$job = $this->getJob();
			if ($job instanceof Tank) {
				$result = $job->getEnergy() > 0;
			} else {
				$result = false;
			}

			if (!$result) {
				$this->cooltimeHandler->start($this->getCooltime());
				$this->active = false;
				$this->tick = 0;
				$job->setAbility(new RegrowthAbility($job));
			}

			return $result;
		});

		$this->registerTask($task);

		PlayerUtil::broadcastSound($this->player, "beacon.activate", 1.5, 0.9);

		return ActionResult::MISS();
	}

	/**
	 * @param EntityDamageEvent $event
	 * 
	 * @return void
	 * 
	 * @priority MONITOR
	 */
	public function onEntityDamage(EntityDamageEvent $event) {
		$entity = $event->getEntity();
		if ($entity instanceof Player) {
			if ($entity->getWorld() == $this->player->getWorld()) {
				if ($entity !== $this->player) {
					if ($this->isActive()) {
						if (
							$event->getCause() == EntityDamageEvent::CAUSE_MAGIC ||
							$event->getCause() == EntityDamageEvent::CAUSE_CONTACT ||
							$event->getCause() == EntityDamageEvent::CAUSE_ENTITY_ATTACK ||
							$event->getCause() == EntityDamageEvent::CAUSE_ENTITY_EXPLOSION ||
							$event->getCause() == EntityDamageEvent::CAUSE_PROJECTILE
						) {
							$distance = $entity->getPosition()->distance($this->player->getPosition());
							if ($distance <= $this->area->get()) {
								$absorp = $event->getModifier(EntityDamageEvent::MODIFIER_ABSORPTION);
								$reduce = $event->getFinalDamage() + (-$absorp);
								$job = $this->getJob();
								if ($job instanceof Tank) {
									$job->addEnergy(-$reduce);
								}
								PlayerUtil::broadcastSound($entity->getPosition(), "item.shield.block", 1.0, 1.0);
								$event->cancel();
								$this->getJob()->getActionListManager()->push(new LineOption("§7{$entity->getName()} §7の §c{$reduce} §7ダメージを防いだ！"));
							}
						}
					}
				}
			}
		}
	}
}
