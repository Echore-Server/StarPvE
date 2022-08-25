<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\job\player\engineer\entity;

use Lyrica0954\MagicParticle\LineParticle;
use Lyrica0954\MagicParticle\ParticleOption;
use Lyrica0954\MagicParticle\SingleParticle;
use Lyrica0954\StarPvE\entity\item\GhostItemEntity;
use Lyrica0954\StarPvE\game\wave\DefaultMonsters;
use Lyrica0954\StarPvE\game\wave\MonsterData;
use Lyrica0954\StarPvE\utils\EntityUtil;
use Lyrica0954\StarPvE\utils\ParticleUtil;
use Lyrica0954\StarPvE\utils\VectorUtil;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\entity\Living;
use pocketmine\math\Vector3;

class GravityBall extends GhostItemEntity {

	protected bool $preparing = false;
	protected bool $active = false;

	public float $limit = (11 * 20);
	public float $area = 5.0;
	public float $period = 10;
	public EffectInstance $effect;
	protected int $attackTick = 0;
	protected int $tick = 0;

	public function onUpdate(int $currentTick): bool {
		$hasUpdate = parent::onUpdate($currentTick);

		if ($this->isOnGround() && (!$this->preparing && !$this->active)) {
			$this->preparing = true;
			$this->gravity = 0.0;
			$this->drag = 0.0;

			# 広がってから引き寄せるパーティクル
		}

		if ($this->preparing || $this->active) {
			$this->tick++;
		} else {
			ParticleUtil::send(
				new SingleParticle,
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

		if ($this->preparing) {
			if ($this->tick <= 20) {
				$motion = (new Vector3(0, 0.01, 0))->multiply($this->tick * 0.5);
				$this->setMotion($motion);
			} elseif ($this->tick > 20 && !$this->active) {
				$this->setMotion(new Vector3(0, 0, 0));
				$this->active = true;
				$this->preparing = false;
			}
		}

		if ($this->active) {
			if ($this->tick > $this->limit) {
				$motion = (new Vector3(0, -0.01, 0))->multiply(($this->tick - $this->limit) * 0.5);
				$this->setMotion($motion);
				if ($this->tick > ($this->limit + 20)) {
					$this->kill();
				}
			} else {
				$this->attackTick += 1;
				ParticleUtil::send(new SingleParticle, $this->getWorld()->getPlayers(), $this->getPosition(), ParticleOption::spawnPacket("minecraft:end_chest", ""));
				if ($this->attackTick >= $this->period) {
					$this->attackTick = 0;
					$par = new LineParticle($this->getPosition(), 3);
					foreach (EntityUtil::getWithinRange(
						VectorUtil::insertWorld(
							$this->getOffsetPosition(
								$this->getPosition()
							),
							$this->getWorld()
						),
						$this->area
					) as $entity) {
						if (MonsterData::isMonster($entity) && $entity instanceof Living) {
							$dist = $this->getPosition()->distance($entity->getPosition());
							$powerM = match (true) {
								(MonsterData::equal($entity, DefaultMonsters::ATTACKER)) => 0.1,
								(MonsterData::equal($entity, DefaultMonsters::HUSK)) => 0,
								default => 0.9
							};
							$power = 1.0 + ($dist * $powerM);

							ParticleUtil::send(
								$par,
								$this->getWorld()->getPlayers(),
								VectorUtil::insertWorld(
									$entity->getEyePos(),
									$entity->getWorld()
								),
								ParticleOption::spawnPacket("minecraft:basic_crit_particle", "")
							);

							if (!$entity->isOnGround()) {
								$power *= 0.4;
							}

							$motion = EntityUtil::modifyKnockback($entity, $this, -$power, 0.0);
							$entity->setMotion($motion);

							$entity->getEffects()->add(clone $this->effect);
						}
					}
				}
			}
		}

		return $hasUpdate;
	}
}
