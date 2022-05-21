<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\job\player\archer\entity;

use Lyrica0954\MagicParticle\CircleParticle;
use Lyrica0954\MagicParticle\EmitterParticle;
use Lyrica0954\MagicParticle\ParticleOption;
use Lyrica0954\StarPvE\entity\Villager;
use Lyrica0954\StarPvE\game\wave\MonsterData;
use Lyrica0954\StarPvE\utils\EffectGroup;
use Lyrica0954\StarPvE\utils\EntityUtil;
use Lyrica0954\StarPvE\utils\PlayerUtil;
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
use pocketmine\math\Facing;
use pocketmine\math\RayTraceResult;
use pocketmine\math\Vector3;
use pocketmine\math\VoxelRayTrace;
use pocketmine\player\Player;
use pocketmine\timings\Timings;

class SpecialArrow extends Arrow {

	protected $pickupMode = self::PICKUP_NONE;

	protected int $age = 0;
	protected bool $activated = false;
	protected ?Vector3 $activatePosition = null;
	protected int $activateFace = -1;
	protected int $activeTick = 0;
	protected int $particleTick = 1000;

	public ?EffectGroup $areaEffects = null;
	public ?EffectGroup $hitEffects = null;
	public float $area = 4.0;
	public float $areaDamage = 1.0;
	public int $duration = (8 * 20);
	public int $period = 10;

	public function getResultDamage(): int {
		$base = parent::getResultDamage();

		if ($this->isCritical()) {
			return ($base * 1.5);
		} else {
			return $base;
		}
	}

	protected function onHitBlock(Block $blockHit, RayTraceResult $hitResult): void {
		parent::onHitBlock($blockHit, $hitResult);
		$this->pickupMode = self::PICKUP_NONE;
		$face = $hitResult->getHitFace();
		$bb = $hitResult->getBoundingBox();
		$dir = VectorUtil::getDirection($face);
		$dir = VectorUtil::reAdd($dir, 0.15);

		$pos = $hitResult->getHitVector();
		$pos->y = $blockHit->getPosition()->getY();
		$pos = $pos->addVector($dir);
		$this->activateFace = $face;
		$this->activatePosition = $pos;
		$this->activated = true;

		if (($owning = $this->getOwningEntity()) instanceof Player) {
			PlayerUtil::playSound($owning, "hit.nylium", 1.5, 1.0);
			PlayerUtil::playSound($owning, "item.trident.riptide_1", 1.0, 0.5);
		}
	}

	protected function onHitEntity(Entity $entityHit, RayTraceResult $hitResult): void {
		parent::onHitEntity($entityHit, $hitResult);
		$effects = ($this->areaEffects ?? (new EffectGroup()));
		$effects->apply($entityHit);

		if (($owning = $this->getOwningEntity()) instanceof Player) {
			PlayerUtil::playSound($owning, "hit.nylium", 1.5, 1.0);
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

			$this->isCollided = $this->onGround = true;
			$this->motion = new Vector3(0, 0, 0);
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

		$min = EntityUtil::getCollisionMin($this);
		$emitter = EmitterParticle::createEmitterForEntity($this, 0.1, 1);
		$players = $this->getWorld()->getPlayers();
		$pos = VectorUtil::insertWorld($min, $this->getWorld());
		$emitter->sendToPlayers($players, $pos, ParticleOption::spawnPacket("minecraft:falling_dust_red_sand_particle", ""));
		$emitter->sendToPlayers($players, $pos, ParticleOption::spawnPacket("minecraft:falling_dust_sand_particle", ""));
		$emitter->sendToPlayers($players, $pos, ParticleOption::spawnPacket("minecraft:falling_dust_top_snow_particle", ""));

		if ($this->activated) {
			$vec = $this->activatePosition;
			if ($vec instanceof Vector3) {
				$this->activeTick += $tickDiff;
				$this->age += $tickDiff;
				$this->particleTick += $tickDiff;

				if ($this->age >= $this->duration) {
					$this->kill();
				}

				if ($this->activeTick >= $this->period) {
					$this->activeTick = 0;
					PlayerUtil::broadcastSound($this, "damage.fallbig", 1.0, 0.3);

					foreach (EntityUtil::getWithinRange($pos, $this->area) as $entity) {
						if (MonsterData::isMonster($entity) && $entity instanceof Living) {
							$effects = ($this->areaEffects ?? (new EffectGroup()));
							$effects->apply($entity);
							$source = new EntityDamageEvent($entity, EntityDamageEvent::CAUSE_PROJECTILE, $this->areaDamage, [], 0);
							$entity->attack($source);
						}
					}
				}

				if ($this->particleTick >= 35) {
					$this->particleTick = 0;

					$par = (new CircleParticle($this->area, 10, 0));
					$pos = VectorUtil::insertWorld($vec, $this->getWorld());
					$par->sendToPlayers($this->getWorld()->getPlayers(), $pos, ParticleOption::spawnPacket("starpve:border_limit", ""));
					$par->sendToPlayers($this->getWorld()->getPlayers(), $pos, ParticleOption::spawnPacket("minecraft:villager_happy", ""));
				}
			}
		}

		return $update;
	}

	public function canCollideWith(Entity $entity): bool {
		return ($entity instanceof Living && (!$entity instanceof Player && !$entity instanceof Villager)) && !$this->onGround;
	}
}
