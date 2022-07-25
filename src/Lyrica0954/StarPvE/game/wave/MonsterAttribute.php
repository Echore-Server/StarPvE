<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\game\wave;

use Lyrica0954\SmartEntity\entity\LivingBase;

class MonsterAttribute {

    public int $health;
    public float $damage;
    public float $speed;

    public function __construct(int $health, float $damage, float $speed) {
        $this->health = $health;
        $this->damage = $damage;
        $this->speed = $speed;
    }

    public function apply(LivingBase $entity) {
        $entity->setMovementSpeed($this->speed);
        $entity->setAttackDamage($this->damage);
        $entity->setMaxHealth($this->health);
        $entity->setHealth($this->health);
    }
}
