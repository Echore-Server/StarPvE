<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\game\monster;

use Lyrica0954\SmartEntity\entity\walking\Zombie as SmartZombie;
use Lyrica0954\StarPvE\utils\HealthBarEntity;

class Zombie extends SmartZombie {
	use HealthBarEntity;

	protected float $reach = 1.6;

	public function getFollowRange(): float {
		return 50;
	}
}
