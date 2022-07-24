<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\job\player\castle\entity;

use Lyrica0954\MagicParticle\CircleParticle;
use Lyrica0954\MagicParticle\LineParticle;
use Lyrica0954\MagicParticle\ParticleOption;
use Lyrica0954\MagicParticle\SingleParticle;
use Lyrica0954\StarPvE\entity\item\GhostItemEntity;
use Lyrica0954\StarPvE\game\wave\MonsterData;
use Lyrica0954\StarPvE\utils\EntityUtil;
use Lyrica0954\StarPvE\utils\PlayerUtil;
use Lyrica0954\StarPvE\utils\VectorUtil;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\math\Vector3;

class TrapDevice extends GhostItemEntity {

	protected bool $active = false;

	protected int $tick = 0;

	/**
	 * @var int[]
	 */
	protected array $attackTick;

	public float $damage = 0;

	public int $duration = 0;

	public int $amount = 0;

	public float $area = 0;

	protected int $count = 0;

	protected int $visTick = 0;

	protected function entityBaseTick(int $tickDiff = 1): bool {
		$hasUpdate = parent::entityBaseTick($tickDiff);

		if ($this->isOnGround() && !$this->active) {
			PlayerUtil::broadcastSound($this, "random.anvil_land", 1.5, 0.2);
			$this->active = true;
			$this->gravity = 0.0;
			$this->drag = 0.0;
			$this->motion = new Vector3(0, 0, 0);
			$this->teleport($this->getPosition()->add(0, 0.5, 0));
		}

		if ($this->active) {
			$this->tick += $tickDiff;
			$this->visTick += $tickDiff;

			if ($this->visTick >= 15) {
				$this->visTick = 0;
				(new CircleParticle($this->area, 6))->sendToPlayers($this->getWorld()->getPlayers(), $this->getPosition(), ParticleOption::spawnPacket("minecraft:falling_dust_scaffolding_particle", ""));
			}

			if ($this->tick >= $this->duration || $this->amount <= $this->count) {
				$this->kill();
			} else {
				foreach (EntityUtil::getWithinRange($this->getPosition(), $this->area) as $entity) {
					if (MonsterData::isMonster($entity)) {
						EntityUtil::slowdown($entity, (1 * 20), 0.7);
						$k = spl_object_hash($entity);
						if (!isset($this->attackTick[$k])) {
							$this->attackTick[$k] = [0, 20];
						}

						#k0: attack
						#k1: visualize

						if ($this->attackTick[$k][0] >= 0) {
							$tick = $this->attackTick[$k][0] += $tickDiff;
							$visTick = $this->attackTick[$k][1] += $tickDiff;
							$particleOption = match (true) {
								$tick >= 40 => (ParticleOption::spawnPacket("starpve:soft_red_gas", "")),
								$tick >= 20 => (ParticleOption::spawnPacket("starpve:soft_yellow_gas", "")),
								default => (ParticleOption::spawnPacket("minecraft:balloon_gas_particle", ""))
							};

							if ($visTick >= 20) {
								$this->attackTick[$k][1] = 0;
								PlayerUtil::broadcastSound($this, "fire.ignite", 0.75, 1.0);
								(new LineParticle($this->getPosition(), 3))->sendToPlayers($this->getWorld()->getPlayers(), $entity->getPosition(), $particleOption);
							}

							if ($tick >= 60) {
								$this->attackTick[$k][0] = -1;
								$this->count++;

								$source = new EntityDamageEvent($entity, EntityDamageEvent::CAUSE_ENTITY_ATTACK, $this->damage);
								$source->setAttackCooldown(0);
								$entity->attack($source);

								EntityUtil::immobile($entity, 6 * 20);
								PlayerUtil::broadcastSound($entity, "mob.allay.hurt", 1.5, 0.6);
							}
						}
					}
				}
			}
		} else {
			(new SingleParticle)->sendToPlayers(
				$this->getWorld()->getPlayers(),
				VectorUtil::insertWorld(
					$this->getOffsetPosition(
						$this->getPosition()
					),
					$this->getWorld()
				),
				ParticleOption::spawnPacket("minecraft:balloon_gas_particle", "")
			);
		}



		return $hasUpdate;
	}
}
