<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\game\monster\boss;

use Lyrica0954\StarPvE\game\monster\Attacker;
use pocketmine\nbt\tag\CompoundTag;

class GiantAttacker extends Attacker {

	protected function initEntity(CompoundTag $nbt): void {
		parent::initEntity($nbt);

		$this->setScale(1.6);
	}

	public function getMotionResistance(): float {
		return 0.25;
	}
}
