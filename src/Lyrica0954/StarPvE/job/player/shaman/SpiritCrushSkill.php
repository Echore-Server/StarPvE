<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\job\player\shaman;

use Lyrica0954\StarPvE\game\wave\MonsterData;
use Lyrica0954\StarPvE\job\AbilityStatus;
use Lyrica0954\StarPvE\job\ActionResult;
use Lyrica0954\StarPvE\job\player\shaman\entity\SpiritEntity;
use Lyrica0954\StarPvE\job\Skill;
use Lyrica0954\StarPvE\utils\EntityUtil;
use Lyrica0954\StarPvE\utils\TaskUtil;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\math\Vector3;
use pocketmine\scheduler\ClosureTask;

class SpiritCrushSkill extends Skill {

	protected ?SpawnSpiritSpell $targetSpell = null;

	public function getName(): string {
		return "幽体クラッシュ";
	}

	public function getDescription(): string {
		return sprintf('');
	}

	protected function init(): void {
		$this->cooltime = new AbilityStatus(30);
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


		$entities = $this->targetSpell->getEntities();

		foreach ($entities as $entity) {
			if ($entity instanceof SpiritEntity) {
				$entity->setMotion(new Vector3(0, 1, 0));
			}
		}

		TaskUtil::delayed(new ClosureTask(function () use ($entities) {
			foreach ($entities as $entity) {
				if ($entity instanceof SpiritEntity) {
					$entity->setMotion(new Vector3(0, -2, 0));
				}
			}

			TaskUtil::delayed(new ClosureTask(function () use ($entities) {
				foreach ($entities as $entity) {
					if ($entity instanceof SpiritEntity) {
						foreach (EntityUtil::getWithinRange($entity->getPosition(), 3, $entity) as $target) {
							if (MonsterData::isMonster($target)) {
								$source = new EntityDamageByEntityEvent($entity, $target, EntityDamageEvent::CAUSE_ENTITY_ATTACK, $entity->getAttackDamage() * 10, [], 0);
								$target->attack($source);
								$target->setMotion(new Vector3(0, 0.7, 0));
							}
						}
					}
				}
			}), 3);
		}), 10);

		return ActionResult::SUCCEEDED();
	}
}
