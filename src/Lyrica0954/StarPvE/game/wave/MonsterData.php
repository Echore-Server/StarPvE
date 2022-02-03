<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\game\wave;

use Lyrica0954\SmartEntity\entity\LivingBase;
use Lyrica0954\SmartEntity\entity\walking\FightingEntity;
use Lyrica0954\StarPvE\game\monster\Attacker;
use Lyrica0954\StarPvE\game\monster\Creeper;
use Lyrica0954\StarPvE\game\monster\Defender;
use Lyrica0954\StarPvE\game\monster\Husk;
use Lyrica0954\StarPvE\game\monster\Skeleton;
use Lyrica0954\StarPvE\game\monster\Spider;
use Lyrica0954\StarPvE\game\monster\Zombie;
use pocketmine\entity\Entity;

class MonsterData {

    const ZOMBIE = Zombie::class;
    const CREEPER = Creeper::class;
    const ATTACKER = Attacker::class;
    const SPIDER = Spider::class;
    const HUSK = Husk::class;
    const SKELETON = Skeleton::class;
    const DEFENDER = Defender::class;

    public string $name;
    public int $count;

    public function __construct(string $name, int $count){
        $this->name = $name;
        $this->count = $count;
    }

    public static function isMonster(Entity $entity){
        return ($entity instanceof FightingEntity);
    }

    public static function equal(Entity $entity, string $class){
        return $entity::class == $class; #関数にする必要ある？
    }
}