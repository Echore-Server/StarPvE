<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\job\player\engineer\entity;

use Lyrica0954\MagicParticle\ParticleOption;
use Lyrica0954\MagicParticle\SingleParticle;
use Lyrica0954\MagicParticle\utils\MolangUtil;
use Lyrica0954\StarPvE\entity\MemoryEntity;
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
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;
use pocketmine\world\Position;

class ToxicBin extends Throwable {

	public float $radius = 0.0;

	public int $duration = 0;

	public float $areaDamage = 0.0;

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
						$id = $target->getId();
						$data->damageCount[$id] ?? $data->damageCount[$id] = 0;
						$dmgCount = $data->damageCount[$id]++;

						$source = new EntityDamageByEntityEvent($this->getOwningEntity() ?? $e, $target, EntityDamageEvent::CAUSE_MAGIC, $data->damage, [], 0.0);
						$source->setAttackCooldown(0);
						$target->attack($source);

						EntityUtil::slowdown($target, 20, 0.85, SlowdownRunIds::get($e::class, $e->getId()));
					}
				}

				PlayerUtil::broadcastSound($e, "random.fizz", 2.4, 0.7);
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
