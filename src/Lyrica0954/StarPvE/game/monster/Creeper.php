<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\game\monster;

use Lyrica0954\SmartEntity\entity\walking\Creeper as SmartCreeper;
use Lyrica0954\StarPvE\game\wave\MonsterData;
use Lyrica0954\StarPvE\utils\HealthBarEntity;
use pocketmine\block\Planks;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityExplodeEvent;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\player\Player;
use pocketmine\world\Explosion;
use pocketmine\world\particle\HugeExplodeSeedParticle;
use pocketmine\world\Position;
use pocketmine\world\sound\ExplodeSound;

class Creeper extends SmartCreeper {
	use HealthBarEntity;

	protected float $reach = 3.0;

	public function getFuseLength(): int {
		return 30;
	}

	public function getFollowRange(): float {
		return 50;
	}

	public function explode(): void {
		$this->spawnExplosion($this->getPosition(), $this->getExplosionRadius());
		$this->kill();
	}

	protected function spawnExplosion(Position $pos, float $size): bool {
		$updateBlocks = [];

		$source = $pos->floor();
		$yield = (1 / $size) * 100;

		$ev = new EntityExplodeEvent($this, $pos, [], $yield);
		$ev->call();
		if ($ev->isCancelled()) {
			return false;
		} else {
			$yield = $ev->getYield();
		}

		$explosionSize = $size * 2;
		$minX = (int) floor($pos->x - $explosionSize - 1);
		$maxX = (int) ceil($pos->x + $explosionSize + 1);
		$minY = (int) floor($pos->y - $explosionSize - 1);
		$maxY = (int) ceil($pos->y + $explosionSize + 1);
		$minZ = (int) floor($pos->z - $explosionSize - 1);
		$maxZ = (int) ceil($pos->z + $explosionSize + 1);

		$explosionBB = new AxisAlignedBB($minX, $minY, $minZ, $maxX, $maxY, $maxZ);

		$list = $this->getWorld()->getNearbyEntities($explosionBB, $this);
		foreach ($list as $entity) {
			if ($entity instanceof Player) {
				$entityPos = $entity->getPosition();
				$distance = $entityPos->distance($pos) / $explosionSize;

				if ($distance <= 1) {
					$motion = $entityPos->subtractVector($pos)->normalize();

					$impact = (1 - $distance) * ($exposure = 1);

					$damage = (int) ((($impact * $impact + $impact) / 2) * 8 * $explosionSize + 1);

					$ev = new EntityDamageByEntityEvent($this, $entity, EntityDamageEvent::CAUSE_ENTITY_EXPLOSION, $damage);


					$entity->attack($ev);
					$motion = $motion->multiply($impact * 2);
					$motion->y += 0.4;
					$motion->x *= 1.3;
					$motion->z *= 1.3;
					$entity->setMotion($motion);
				}
			}
		}

		$this->getWorld()->addParticle($source, new HugeExplodeSeedParticle());
		$this->getWorld()->addSound($source, new ExplodeSound());

		return true;
	}
}
