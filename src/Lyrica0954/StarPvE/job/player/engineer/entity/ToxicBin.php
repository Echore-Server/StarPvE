<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\job\player\engineer\entity;

use Lyrica0954\MagicParticle\ParticleOption;
use Lyrica0954\MagicParticle\SingleParticle;
use Lyrica0954\MagicParticle\utils\MolangUtil;
use Lyrica0954\StarPvE\entity\EntityStateManager;
use Lyrica0954\StarPvE\entity\MemoryEntity;
use Lyrica0954\StarPvE\entity\state\ElectrificationState;
use Lyrica0954\StarPvE\game\wave\MonsterData;
use Lyrica0954\StarPvE\utils\EntityUtil;
use Lyrica0954\StarPvE\utils\ParticleUtil;
use Lyrica0954\StarPvE\utils\PlayerUtil;
use Lyrica0954\StarPvE\utils\SlowdownRunIds;
use pocketmine\block\Block;
use pocketmine\entity\Entity;
use pocketmine\entity\EntitySizeInfo;
use pocketmine\entity\Living;
use pocketmine\entity\Location;
use pocketmine\entity\projectile\Projectile;
use pocketmine\entity\projectile\Throwable;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\ProjectileHitEvent;
use pocketmine\item\Durable;
use pocketmine\math\RayTraceResult;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\AddActorPacket;;

use pocketmine\network\mcpe\protocol\SyncActorPropertyPacket;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;
use pocketmine\network\mcpe\protocol\types\entity\PropertySyncData;
use pocketmine\world\Position;

class ToxicBin extends Throwable {

	public float $radius = 0.0;

	public int $duration = 0;

	public float $areaDamage = 0.0;

	public bool $expandEnabled = false;

	public static function getNetworkTypeId(): string {
		return EntityIds::SPLASH_POTION;
	}

	protected function getInitialSizeInfo(): EntitySizeInfo {
		return new EntitySizeInfo(0.1, 0.1);
	}

	protected function entityBaseTick(int $tickDiff = 1): bool {
		$updated = parent::entityBaseTick($tickDiff);



		return $updated;
	}

	protected function onHitBlock(Block $blockHit, RayTraceResult $hitResult): void {
	}

	protected function onHitEntity(Entity $entityHit, RayTraceResult $hitResult): void {
	}

	protected function onHit(ProjectileHitEvent $event): void {
		$result = $event->getRayTraceResult();
		$vec = $result->getHitVector();

		$data = new \stdClass;
		$data->tick = 0;
		$data->damageTick = 0;
		$data->lastParticleTick = 0;
		$data->radius = $this->radius;
		$data->damage = $this->areaDamage;
		$data->duration = $this->duration;
		$data->damageCount = [];
		$data->hit = [];
		$data->radiusPerc = 1.0;

		$entity = new MemoryEntity(Location::fromObject($vec, $this->getWorld()), null, 0, 0);
		$entity->addTickHook(function (MemoryEntity $e, int $tickDiff = 1) use ($data): void {
			$data->tick += $tickDiff;
			$data->damageTick += $tickDiff;
			if ($data->tick - $data->lastParticleTick > 10) {
				$molang = [];
				$molang[] = MolangUtil::member("color", [
					["r", 1.0],
					["g", 1.0],
					["b", 0.0],
					["a", 1.0]
				]);
				$molang[] = MolangUtil::variable("radius", $data->radius);
				$molang[] = MolangUtil::variable("lifetime", 1.75);
				$molang[] = MolangUtil::variable("rate", 75);


				ParticleUtil::send(
					new SingleParticle,
					$e->getWorld()->getPlayers(),
					Position::fromObject($e->getPosition()->add(0, 0.15, 0), $e->getWorld()),
					ParticleOption::spawnPacket("starpve:range", MolangUtil::encode($molang))
				);

				$data->lastParticleTick = $data->tick;
			}

			if ($data->damageTick > 10) {
				$data->damageTick = 0;

				foreach (EntityUtil::getWithinRange($e->getPosition(), $data->radius, $e) as $target) {
					if (MonsterData::isMonster($target)) {
						$damage = $data->damage;
						if (EntityStateManager::has($target->getId(), ElectrificationState::class)) {
							if (!isset($data->hit[$target->getId()])) {
								$pk = AddActorPacket::create(0, Entity::nextRuntimeId(), EntityIds::LIGHTNING_BOLT, $this->getPosition(), Vector3::zero(), 0, 0, 0, 0, [], [], new PropertySyncData([], []), []);
								foreach ($this->getWorld()->getPlayers() as $player) {
									$player->getNetworkSession()->sendDataPacket($pk);
								}
							}

							$damage *= 2;
						}
						$data->hit[$target->getId()] = null;
						$id = $target->getId();
						$data->damageCount[$id] ?? $data->damageCount[$id] = 0;
						$dmgCount = $data->damageCount[$id]++;

						$source = new EntityDamageByEntityEvent($this->getOwningEntity() ?? $e, $target, EntityDamageEvent::CAUSE_MAGIC, $damage, [], 0.0);
						$source->setAttackCooldown(0);
						$target->attack($source);

						EntityUtil::slowdown($target, 20, 0.85, SlowdownRunIds::get($e::class, $e->getId()));
					}
				}

				PlayerUtil::broadcastSound($e, "random.fizz", 2.4, 0.7);

				if ($this->expandEnabled) {
					$data->radiusPerc += 0.03;
					$data->radius = $this->radius * $data->radiusPerc;
				}
			}


			if ($data->tick > $data->duration) {
				$e->close();
			}
		});

		PlayerUtil::broadcastSound($this, "item.trident.thunder", 2.0, 0.45);
		PlayerUtil::broadcastSound($this, "random.glass", 1.25, 0.5);

		$this->flagForDespawn();
	}
}
