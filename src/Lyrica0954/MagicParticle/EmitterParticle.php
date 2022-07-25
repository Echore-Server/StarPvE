<?php

declare(strict_types=1);

namespace Lyrica0954\MagicParticle;

use Lyrica0954\StarPvE\utils\RandomUtil;
use pocketmine\entity\Entity;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Vector3;
use pocketmine\world\Position;

class EmitterParticle extends SendableParticle {

	public static function createEmitterForEntity(Entity $entity, float $expand, int $amount) {
		$aabb = $entity->getBoundingBox();
		$size = $entity->size;
		$expand = 0.3;
		$par = new EmitterParticle(
			new Vector3(
				$expand,
				$expand,
				$expand
			),
			new Vector3(
				$size->getWidth() + $expand,
				$size->getHeight() + $expand,
				$size->getWidth() + $expand
			),
			$amount
		);
		return $par;
	}

	public function __construct(private Vector3 $minRange, private Vector3 $maxRange, private int $amount) {
	}

	public function draw(Position $pos): array {
		$min = $this->minRange->abs();
		$max = $this->maxRange->abs();
		$positions = [];
		for ($i = 0; $i < $this->amount; $i++) {
			$new = clone $pos;
			$new->x += RandomUtil::rand_float(-$min->x, $max->x);
			$new->y += RandomUtil::rand_float(-$min->y, $max->y);
			$new->z += RandomUtil::rand_float(-$min->z, $max->z);
			$positions[] = $new;
		}

		return $positions;
	}
}
