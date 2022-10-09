<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\job\player\archer\entity;

use Lyrica0954\MagicParticle\CircleParticle;
use Lyrica0954\MagicParticle\EmitterParticle;
use Lyrica0954\MagicParticle\ParticleOption;
use Lyrica0954\MagicParticle\SingleParticle;
use Lyrica0954\MagicParticle\utils\MolangUtil;
use Lyrica0954\StarPvE\entity\EntityStateManager;
use Lyrica0954\StarPvE\entity\state\FatalWoundState;
use Lyrica0954\StarPvE\entity\Villager;
use Lyrica0954\StarPvE\game\wave\MonsterData;
use Lyrica0954\StarPvE\utils\EffectGroup;
use Lyrica0954\StarPvE\utils\EntityUtil;
use Lyrica0954\StarPvE\utils\ParticleUtil;
use Lyrica0954\StarPvE\utils\PlayerUtil;
use Lyrica0954\StarPvE\utils\TaskUtil;
use Lyrica0954\StarPvE\utils\VectorUtil;
use pocketmine\block\Block;
use pocketmine\entity\Entity;
use pocketmine\entity\Living;
use pocketmine\entity\Location;
use pocketmine\entity\projectile\Arrow;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\ProjectileHitBlockEvent;
use pocketmine\event\entity\ProjectileHitEntityEvent;
use pocketmine\event\entity\ProjectileHitEvent;
use pocketmine\math\Facing;
use pocketmine\math\RayTraceResult;
use pocketmine\math\Vector3;
use pocketmine\math\VoxelRayTrace;
use pocketmine\player\Player;
use pocketmine\scheduler\ClosureTask;
use pocketmine\timings\Timings;

class WoundArrow extends Arrow {

	protected $pickupMode = self::PICKUP_NONE;

	public float $hitDamage = 0.0;

	protected array $damaged = [];

	public float $area = 0.0;

	public int $duration = 0;

	public function getResultDamage(): int {
		return -1;
	}

	protected function onHitBlock(Block $blockHit, RayTraceResult $hitResult): void {
		$this->flagForDespawn();
	}

	protected function onHitEntity(Entity $entityHit, RayTraceResult $hitResult): void {
		if (!in_array($entityHit->getId(), $this->damaged)) {
			$source = new EntityDamageByEntityEvent($this->getOwningEntity() ?? $this, $entityHit, EntityDamageEvent::CAUSE_PROJECTILE, $this->hitDamage, [], 0.0);
			$entityHit->attack($source);
			$state = new FatalWoundState($entityHit, 2.0);
			$id = EntityStateManager::nextStateId();
			EntityStateManager::start($state, $id);

			TaskUtil::delayed(new ClosureTask(function () use ($entityHit, $id) {
				EntityStateManager::end($entityHit->getId(), $id);
			}), max(0, $this->duration));

			$this->damaged[] = $entityHit->getId();
		}
	}

	protected function move(float $dx, float $dy, float $dz): void {
		$this->blocksAround = null;

		Timings::$entityMove->startTiming();

		$start = $this->location->asVector3();
		$end = $start->add($dx, $dy, $dz);

		$blockHit = null;
		$entityHit = null;
		$hitResult = null;

		foreach (VoxelRayTrace::betweenPoints($start, $end) as $vector3) {
			$block = $this->getWorld()->getBlockAt($vector3->x, $vector3->y, $vector3->z);

			$blockHitResult = $this->calculateInterceptWithBlock($block, $start, $end);
			if ($blockHitResult !== null) {
				$end = $blockHitResult->hitVector;
				$blockHit = $block;
				$hitResult = $blockHitResult;
				break;
			}
		}

		$entityDistance = PHP_INT_MAX;

		$newDiff = $end->subtractVector($start);
		foreach ($this->getWorld()->getCollidingEntities($this->boundingBox->addCoord($newDiff->x, $newDiff->y, $newDiff->z)->expand(1, 1, 1), $this) as $entity) {
			if ($entity->getId() === $this->getOwningEntityId() && $this->ticksLived < 5) {
				continue;
			}

			if (!$this->canCollideWith($entity)) {
				continue;
			}

			$entityBB = $entity->boundingBox->expandedCopy(0.3, 0.3, 0.3);
			$entityHitResult = $entityBB->calculateIntercept($start, $end);

			if ($entityHitResult === null) {
				continue;
			}

			$distance = $this->location->distanceSquared($entityHitResult->hitVector);

			if ($distance < $entityDistance) {
				$entityDistance = $distance;
				$entityHit = $entity;
				$hitResult = $entityHitResult;
				$end = $entityHitResult->hitVector;
			}
		}

		$this->location = Location::fromObject(
			$end,
			$this->location->world,
			$this->location->yaw,
			$this->location->pitch
		);
		$this->recalculateBoundingBox();

		if ($hitResult !== null) {
			/** @var ProjectileHitEvent|null $ev */
			$ev = null;
			if ($entityHit !== null) {
				$ev = new ProjectileHitEntityEvent($this, $hitResult, $entityHit);
			} elseif ($blockHit !== null) {
				$ev = new ProjectileHitBlockEvent($this, $hitResult, $blockHit);
			} else {
				assert(false, "unknown hit type");
			}

			if ($ev !== null) {
				$ev->call();
				$this->onHit($ev);

				if ($ev instanceof ProjectileHitEntityEvent) {
					$this->onHitEntity($ev->getEntityHit(), $ev->getRayTraceResult());
				} elseif ($ev instanceof ProjectileHitBlockEvent) {
					$this->onHitBlock($ev->getBlockHit(), $ev->getRayTraceResult());
				}
			}
		} else {
			$this->isCollided = $this->onGround = false;
			$this->blockHit = null;

			//recompute angles...
			$f = sqrt(($this->motion->x ** 2) + ($this->motion->z ** 2));
			$this->setRotation(
				atan2($this->motion->x, $this->motion->z) * 180 / M_PI,
				atan2($this->motion->y, $f) * 180 / M_PI
			);
		}

		$this->getWorld()->onEntityMoved($this);
		$this->checkBlockIntersections();

		Timings::$entityMove->stopTiming();
	}

	protected function entityBaseTick(int $tickDiff = 1): bool {
		$update = parent::entityBaseTick($tickDiff);

		$players = $this->getWorld()->getPlayers();
		ParticleUtil::send(new SingleParticle, $players, $this->getPosition(), ParticleOption::spawnPacket("minecraft:sonic_explosion", ""));

		return $update;
	}

	public function canCollideWith(Entity $entity): bool {
		return ($entity instanceof Living && (!$entity instanceof Player && !$entity instanceof Villager)) && !$this->onGround && !in_array($entity->getId(), $this->damaged);
	}
}
