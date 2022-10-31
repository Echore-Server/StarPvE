<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\job\player\shaman;

use Lyrica0954\StarPvE\job\Ability;
use Lyrica0954\StarPvE\job\AbilityStatus;
use Lyrica0954\StarPvE\job\ActionResult;
use Lyrica0954\StarPvE\job\player\shaman\entity\SpiritEntity;
use pocketmine\math\Vector3;

class AssaultAbility extends Ability {

	protected ?SpawnSpiritSpell $targetSpell = null;

	public function getName(): string {
		return "突撃";
	}

	public function getDescription(): string {
		return sprintf('§b発動時: §fすべての召喚済みの霊体を自分の方向に突撃させる。
ダメージ: 霊体攻撃力 x §c5§f');
	}

	protected function init(): void {
		$this->area = new AbilityStatus(2.5);
		$this->cooltime = new AbilityStatus(4.0 * 20);
	}

	protected function onActivate(): ActionResult {
		if ($this->targetSpell === null) {
			foreach ($this->getJob()->getSpells() as $spell) {
				if ($spell instanceof SpawnSpiritSpell) {
					$this->targetSpell = $spell;
					break;
				}
			}

			if ($this->targetSpell === null) return ActionResult::FAILED();
		}


		$area = $this->area->get();
		foreach ($this->targetSpell->getEntities() as $entity) {
			if ($entity->isAlive() && !$entity->isClosed()) {
				if ($entity instanceof SpiritEntity) {
					$motion = $this->player->getPosition()->subtractVector($entity->getPosition())->normalize()->multiply(1.6);
					$motion->y = 0.7;
					$entity->assault($area);
					$entity->setMotion($motion);
				}
			}
		}

		return ActionResult::SUCCEEDED();
	}
}
