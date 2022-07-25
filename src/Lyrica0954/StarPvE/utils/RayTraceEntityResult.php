<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\utils;

use pocketmine\entity\Entity;
use pocketmine\math\RayTraceResult;
use pocketmine\math\Vector3;

class RayTraceEntityResult extends RayTraceResult {

    public Entity $entity;

    public function __construct(Entity $entity, int $hitFace, Vector3 $hitVector) {
        parent::__construct($entity->getBoundingBox(), $hitFace, $hitVector);
        $this->entity = $entity;
    }

    public function getEntity(): Entity {
        return $this->entity;
    }
}
