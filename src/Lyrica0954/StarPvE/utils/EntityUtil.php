<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\utils;

use Generator;
use Lyrica0954\SmartEntity\entity\LivingBase;
use Lyrica0954\SmartEntity\utils\VectorUtil;
use Lyrica0954\StarPvE\game\wave\MonsterData;
use pocketmine\entity\Attribute;
use pocketmine\entity\Entity;
use pocketmine\entity\Living;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\Listener;
use pocketmine\math\RayTraceResult;
use pocketmine\math\Vector2;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\plugin\Plugin;
use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\ClosureTask;
use pocketmine\scheduler\TaskHandler;
use pocketmine\Server;
use pocketmine\world\Position;
use pocketmine\world\World;

class EntityUtil implements Listener {

	const DAMAGE_MODIFIER_ADJUST = 99;
	const DAMAGE_MODIFIER_PERCENTAGE = 100;

	public static array $multiplyDamage = [];

	public static ?EntityUtil $instance = null;

	/**
	 * @var array{hash: string, info: (TaskHandler|int)[]}
	 */
	protected static array $immobile = [];

	/**
	 * @var array{hash: string, info: (TaskHandler|int)[]}
	 */
	protected static array $slowdown = [];

	public function init(PluginBase $plugin): void {
		self::$instance = $this;
		$plugin->getServer()->getPluginManager()->registerEvents($this, $plugin);
	}

	/**
	 * @param Position $pos
	 * @param float $range
	 * 
	 * @return Entity[]
	 */
	public static function getWithinRange(Position $pos, float $range, ?Entity $host = null): array {
		$entities = [];
		foreach ($pos->getWorld()->getEntities() as $entity) { #array_filter とかよりforeachのほうが軽いらしい
			if ($entity->isAlive()) {
				if (VectorUtil::distanceToAABB($pos, $entity->getBoundingBox()) <= $range) {
					if ($host !== $entity) {
						$entities[] = $entity;
					}
				}
			}
		}
		return $entities;
	}

	/**
	 * @param Position $pos
	 * @param float $min
	 * @param float $max
	 * 
	 * @return Entity[]
	 */
	public static function getWithin(Position $pos, float $min, float $max): array {
		$entities = [];
		foreach ($pos->getWorld()->getEntities() as $entity) {
			if ($entity->isAlive()) {
				$dist = VectorUtil::distanceToAABB($pos, $entity->getBoundingBox());
				if ($dist <= $max && $dist >= $min) {
					$entities[] = $entity;
				}
			}
		}
		return $entities;
	}

	/**
	 * @param Position $pos
	 * @param float $min
	 * @param float $max
	 * 
	 * @return Entity[]
	 */
	public static function getWithinPlane(Position $pos, float $min, float $max): array {
		$entities = [];
		foreach ($pos->getWorld()->getEntities() as $entity) {
			if ($entity->isAlive()) {
				$ep = $entity->getPosition();
				$ev2 = new Vector2($ep->x, $ep->z);
				$dist = $ev2->distance(new Vector2($pos->x, $pos->z));
				if ($dist <= $max && $dist >= $min) {
					$entities[] = $entity;
				}
			}
		}
		return $entities;
	}

	/**
	 * @param Vector2 $pos
	 * @param World $world
	 * @param float $range
	 * 
	 * @return Entity[]
	 */
	public static function getWithinRangePlane(Vector2 $pos, World $world, float $range): array {
		$entities = [];
		foreach ($world->getEntities() as $entity) {
			if ($entity->isAlive()) {
				$ep = $entity->getPosition();
				$ev2 = new Vector2($ep->x, $ep->z);
				$dist = $ev2->distance($pos);
				if ($dist <= $range) {
					$entities[] = $entity;
				}
			}
		}

		return $entities;
	}

	public static function getNearest(Position $pos, float $maxDistance = PHP_INT_MAX): ?Entity {
		$ndist = $maxDistance;
		$nent = null;

		foreach ($pos->getWorld()->getEntities() as $entity) {
			if ($entity->isAlive()) {
				$dist = $entity->getPosition()->distance($pos);
				if ($dist < $ndist) {
					$nent = $entity;
					$ndist = $dist;
				}
			}
		}

		return $nent;
	}


	public static function getNearestMonster(Position $pos, float $maxDistance = PHP_INT_MAX): ?Entity {
		$ndist = $maxDistance;
		$nent = null;

		foreach ($pos->getWorld()->getEntities() as $entity) {
			if (MonsterData::isMonster($entity) && $entity->isAlive()) {
				$dist = $entity->getPosition()->distance($pos);
				if ($dist < $ndist) {
					$nent = $entity;
					$ndist = $dist;
				}
			}
		}

		return $nent;
	}

	/**
	 * @param Position $pos
	 * @param Vector3|null $expand
	 * 
	 * @return Player[]
	 */
	public static function getPlayersInsideVector(Position $pos, ?Vector3 $expand = null): array {
		$expand = ($expand === null) ? (new Vector3(0, 0, 0)) : $expand;
		$players = [];
		foreach ($pos->getWorld()->getPlayers() as $player) {
			if ($player->isAlive() && !$player->isSpectator()) {
				if ($player->getBoundingBox()->expandedCopy($expand->x, $expand->y, $expand->z)->isVectorInside($pos)) {
					$players[] = $player;
				}
			}
		}

		return $players;
	}

	public static function immobile(Entity $entity, int $duration): void {
		$duration = max(0, $duration);
		if ($duration > 0) {
			$h = spl_object_hash($entity);
			$data = self::$immobile[$h] ?? [0 => null, 1 => null];
			$tick = $data[0];
			$handler = $data[1];
			if ($handler instanceof TaskHandler && is_int($tick)) {
				$elapsed = Server::getInstance()->getTick() - $tick;
				$remain = $handler->getDelay() - $elapsed;
				if ($remain < $duration) {
					$handler->cancel();
					self::$immobile[$h] = [
						0 => Server::getInstance()->getTick(),
						1 => TaskUtil::delayed(new ClosureTask(function () use ($entity, $h) {
							$entity->setImmobile(false);
							unset(self::$immobile[$h]);
						}), $duration)
					];
					return;
				}
			}
			$entity->setImmobile(true);

			self::$immobile[$h] = [
				0 => Server::getInstance()->getTick(),
				1 => TaskUtil::delayed(new ClosureTask(function () use ($entity, $h) {
					$entity->setImmobile(false);
					unset(self::$immobile[$h]);
				}), $duration)
			];
		}
	}

	public static function slowdown(Living $entity, int $duration, float $multiplier, int|string $runId) {
		$duration = max(0, $duration);
		if ($duration > 0) {
			$h = spl_object_hash($entity);
			self::$slowdown[$h][$runId] ?? self::$slowdown[$h] = [];
			$data = self::$slowdown[$h][$runId] ?? [0 => null, 1 => null];
			$tick = $data[0];
			$handler = $data[1];
			if ($handler instanceof TaskHandler && is_int($tick)) {
				$elapsed = Server::getInstance()->getTick() - $tick;
				$remain = $handler->getDelay() - $elapsed;
				if ($remain < $duration) {
					$handler->cancel();
					self::$slowdown[$h][$runId] = [
						0 => Server::getInstance()->getTick(),
						1 => TaskUtil::delayed(new ClosureTask(function () use ($entity, $h, $multiplier, $runId) {
							$entity->setMovementSpeed($entity->getMovementSpeed() / $multiplier);
							unset(self::$slowdown[$h][$runId]);
						}), $duration)
					];
					return;
				}
			}
			$entity->setMovementSpeed($entity->getMovementSpeed() * $multiplier);

			self::$slowdown[$h][$runId] = [
				0 => Server::getInstance()->getTick(),
				1 => TaskUtil::delayed(new ClosureTask(function () use ($entity, $h, $multiplier, $runId) {
					$entity->setMovementSpeed($entity->getMovementSpeed() / $multiplier);
					unset(self::$slowdown[$h][$runId]);
				}), $duration)
			];
		}
	}

	/**
	 * @param Position $pos
	 * @param string[] $without
	 * @param float $maxDistance
	 * 
	 * @return Entity|null
	 */
	public static function getNearestMonsterWithout(Position $pos, array $without, float $maxDistance = PHP_INT_MAX): ?Entity {
		$ndist = $maxDistance;
		$nent = null;

		foreach ($pos->getWorld()->getEntities() as $entity) {
			if (MonsterData::isMonster($entity) && $entity->isAlive()) {
				if (!in_array(spl_object_hash($entity), $without)) {
					$dist = $entity->getPosition()->distance($pos);
					if ($dist < $ndist) {
						$nent = $entity;
						$ndist = $dist;
					}
				}
			}
		}

		return $nent;
	}

	public static function getRandomWithinRange(Position $pos, float $range): ?Entity {
		$entities = self::getWithinRange($pos, $range);
		if (count($entities) > 0) {
			return $entities[array_rand($entities)] ?? null;
		} else {
			return null;
		}
	}

	/**
	 * @param Entity $entity
	 * @param float $reach
	 * 
	 * @return RayTraceEntityResult[]
	 */
	public static function getLineOfSight(Entity $entity, float $reach, ?Vector3 $expand = null): array {
		$expand = ($expand !== null) ? $expand : (new Vector3(0, 0, 0));
		$dir = $entity->getDirectionVector();
		$min = $entity->getEyePos();
		$max = $min->addVector($dir->multiply($reach));

		$entities = [];
		foreach ($entity->getWorld()->getEntities() as $target) {
			if ($entity !== $target) {
				if ($target instanceof Living) {
					if ($target->isAlive()) {
						$result = $target->getBoundingBox()->expandedCopy($expand->x, $expand->y, $expand->z)->calculateIntercept($min, $max);

						if ($result instanceof RayTraceResult) {
							$entities[] = new RayTraceEntityResult($target, $result->getHitFace(), $result->getHitVector());
						}
					}
				}
			}
		}

		return $entities;
	}

	public static function modifyKnockback(Entity $entity, Entity $attacker, float $xz = 1.0, float $y = 1.0): Vector3 {
		$epos = $entity->getPosition();
		$apos = $attacker->getPosition();
		$deltaX = $epos->x - $apos->x;
		$deltaZ = $epos->z - $apos->z;
		$motion = self::calculateKnockback($entity, $deltaX, $deltaZ);
		$motion->x *= $xz;
		$motion->y *= $y;
		$motion->z *= $xz;
		return $motion;
	}

	public static function getCollisionMin(Entity $entity) {
		$bb = $entity->getBoundingBox();
		return new Vector3($bb->minX, $bb->minY, $bb->minZ);
	}

	public static function getCollisionMax(Entity $entity) {
		$bb = $entity->getBoundingBox();
		return new Vector3($bb->maxX, $bb->maxY, $bb->maxZ);
	}

	public static function setMaxHealthSynchronously(Entity $entity, int $maxHealth) {
		$health = $entity->getHealth();
		if ($entity->getMaxHealth() <= $maxHealth) { #増加
			if ($health >= $entity->getMaxHealth()) {
				$health = $maxHealth;
			} else {
				$percentage = ($health / $entity->getMaxHealth());
				$health = ($maxHealth * $percentage);
			}
		} else {
			if ($health >= $maxHealth) {
				$health = $maxHealth;
			}
		}

		$entity->setMaxHealth($maxHealth);
		$entity->setHealth($health);
	}

	public static function addMaxHealthSynchronously(Entity $entity, int $add) {
		self::setMaxHealthSynchronously($entity, ($entity->getMaxHealth() + $add));
	}

	public static function setHealthSynchronously(Entity $entity, float $health) {
		$maxHealth = $entity->getMaxHealth();
		if ($health > $maxHealth) {
			$health = $maxHealth;
		}

		$entity->setHealth($health);
	}

	public static function attackEntity(EntityDamageByEntityEvent $source, float $xz = 1.0, float $y = 1.0, bool $forceMotion = false): Vector3 {
		$source->setKnockBack(0);
		$entity = $source->getEntity();
		$damager = $source->getDamager();

		$kb = self::modifyKnockback($entity, $damager, $xz, $y);

		$entity->attack($source);
		if (!$source->isCancelled() || $forceMotion) {
			$entity->setMotion($kb);
			return $kb;
		}
		return new Vector3(0, 0, 0);
	}

	public static function calculateKnockback(Entity $entity, float $x, float $z, float $base = 0.4): Vector3 {
		$f = sqrt($x * $x + $z * $z);
		if ($f <= 0) {
			return new Vector3(0, 0, 0);
		}
		if (mt_rand() / mt_getrandmax() > ($entity->getAttributeMap()->get(Attribute::KNOCKBACK_RESISTANCE)?->getValue() ?? 0.0)) {
			$f = 1 / $f;

			$motion = clone $entity->getMotion();

			$motion->x /= 2;
			$motion->y /= 2;
			$motion->z /= 2;
			$motion->x += $x * $f * $base;
			$motion->y += $base;
			$motion->z += $z * $f * $base;

			if ($motion->y > $base) {
				$motion->y = $base;
			}

			return $motion;
		} else {
			return new Vector3(0, 0, 0);
		}
	}


	public static function multiplyFinalDamage(EntityDamageEvent $source, float $multiplier): void {
		$before = $source->getModifier(self::DAMAGE_MODIFIER_PERCENTAGE);
		$finalDamage = $source->getFinalDamage(); # + (-$before);
		$subtractDamage = ($finalDamage) * (1 - $multiplier);
		$source->setModifier(
			$before - $subtractDamage,
			self::DAMAGE_MODIFIER_PERCENTAGE
		);
	}

	public static function addFinalDamage(EntityDamageEvent $source, float $add): void {
		$before = $source->getModifier(self::DAMAGE_MODIFIER_ADJUST);
		$source->setModifier(
			$before + $add,
			self::DAMAGE_MODIFIER_ADJUST
		);
	}

	public static function multiplyDamageFor(Entity $entity, float $multiplier, int $duration) {
		$duration = max(0, $duration);
		if ($duration > 0) {
			$h = spl_object_hash($entity);
			self::$multiplyDamage[$h] = [$entity, $multiplier];
			TaskUtil::delayed(new ClosureTask(function () use ($h) {
				unset(self::$multiplyDamage[$h]);
			}), $duration);
		}
	}

	public function onEntityDamage(EntityDamageEvent $event) {
		$entity = $event->getEntity();

		$h = spl_object_hash($entity);

		$data = self::$multiplyDamage[$h] ?? null;
		if ($data !== null) {
			$multiplier = $data[1];
			self::multiplyFinalDamage($event, $multiplier);
		}
	}
}
