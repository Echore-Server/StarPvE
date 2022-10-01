<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\job\player\tank;

use Lyrica0954\StarPvE\game\wave\MonsterData;
use Lyrica0954\StarPvE\job\Ability;
use Lyrica0954\StarPvE\job\AbilityStatus;
use Lyrica0954\StarPvE\job\ActionResult;
use Lyrica0954\StarPvE\translate\DescriptionTranslator;
use Lyrica0954\StarPvE\utils\EffectGroup;
use Lyrica0954\StarPvE\utils\EntityUtil;
use Lyrica0954\StarPvE\utils\PlayerUtil;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\event\entity\EntityDamageEvent;

class EnergyPulseAbility extends Ability {

	public function getName(): string {
		return "エネルギーパルス";
	}

	public function getDescription(): string {
		return sprintf('§c15§f エネルギーを消費して、エネルギーフィールド内の敵全てを §c4秒§f スタンさせる。');
	}

	protected function init(): void {
		$this->cooltime = new AbilityStatus(5 * 20);
	}

	protected function onActivate(): ActionResult {
		$skill = $this->getJob()->getSkill();
		$job = $this->getJob();
		if ($skill instanceof EnergyFieldSkill && $job instanceof Tank) {
			$cost = 15;
			if ($job->getEnergy() >= 400) {
				$cost *= 3;
			}
			$job->addEnergy(-$cost);
			$area = $skill->getArea()->get();

			foreach (EntityUtil::getWithinRange($this->player->getPosition(), $area) as $entity) {
				if (MonsterData::isMonster($entity)) {
					EntityUtil::immobile($entity, 4 * 20);

					if ($job->getEnergy() >= 400) {
						$source = new EntityDamageEvent($entity, EntityDamageEvent::CAUSE_MAGIC, 32.0);
						$source->setAttackCooldown(1);
						$entity->attack($source);
					}
				}
			}

			PlayerUtil::broadcastSound($this->player, "respawn_anchor.set_spawn", 1.2, 1.0);
		}
		return ActionResult::SUCCEEDED();
	}
}
