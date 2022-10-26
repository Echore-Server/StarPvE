<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\game\wave;

use Lyrica0954\SmartEntity\entity\Friend;
use Lyrica0954\SmartEntity\entity\LivingBase;
use Lyrica0954\SmartEntity\entity\walking\FightingEntity;
use Lyrica0954\StarPvE\game\monster\Attacker;
use Lyrica0954\StarPvE\game\monster\boss\Stray;
use Lyrica0954\StarPvE\game\monster\boss\ZombieLord;
use Lyrica0954\StarPvE\game\monster\Creeper;
use Lyrica0954\StarPvE\game\monster\Defender;
use Lyrica0954\StarPvE\game\monster\Husk;
use Lyrica0954\StarPvE\game\monster\Skeleton;
use Lyrica0954\StarPvE\game\monster\Spider;
use Lyrica0954\StarPvE\game\monster\Zombie;
use pocketmine\entity\Entity;
use pocketmine\player\Player;

class MonsterData {

	public string $name;
	public int $count;

	public ?SpawnAnimation $animation;

	public function __construct(string $name, int $count, ?SpawnAnimation $animation = null) {
		$this->name = $name;
		$this->count = $count;
		$this->animation = $animation;
	}

	public static function isMonster(Entity $entity): bool {
		return (($entity instanceof FightingEntity) || $entity instanceof Attacker) && !$entity instanceof Friend;
	}

	public static function isAlly(Entity $entity): bool {
		return $entity instanceof Player || ($entity instanceof Friend);
	}

	public static function isActiveAlly(Entity $entity): bool {
		return self::isAlly($entity) && ($entity instanceof Player ? ($entity->isSurvival(true) || $entity->isAdventure(true)) : true);
	}

	public static function equal(Entity $entity, string $class) {
		return $entity::class == $class; #関数にする必要ある？
	}
}
