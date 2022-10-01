<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\job\player\healer;

use Lyrica0954\MagicParticle\CircleParticle;
use Lyrica0954\MagicParticle\EmitterParticle;
use Lyrica0954\MagicParticle\LineParticle;
use Lyrica0954\MagicParticle\ParticleOption;
use Lyrica0954\MagicParticle\SingleParticle;
use Lyrica0954\StarPvE\entity\Villager;
use Lyrica0954\StarPvE\job\AbilityStatus;
use Lyrica0954\StarPvE\job\ActionResult;
use Lyrica0954\StarPvE\job\Skill;
use Lyrica0954\StarPvE\StarPvE;
use Lyrica0954\StarPvE\translate\DescriptionTranslator;
use Lyrica0954\StarPvE\utils\EffectGroup;
use Lyrica0954\StarPvE\utils\EntityUtil;
use Lyrica0954\StarPvE\utils\ParticleUtil;
use Lyrica0954\StarPvE\utils\PlayerUtil;
use Lyrica0954\StarPvE\utils\VectorUtil;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\event\entity\EntityRegainHealthEvent;
use pocketmine\player\Player;

class ConcordeSkill extends Skill {

	/**
	 * @var AbilityStatus
	 */
	protected AbilityStatus $heal;

	public function getName(): string {
		return "フュージョン";
	}

	public function getDescription(): String {
		$area = DescriptionTranslator::number($this->area, "m");
		$heal = DescriptionTranslator::health($this->heal);
		return
			sprintf('§b発動時(1):§f %1$s 以内の味方の中で一番体力が低いプレイヤーを全回復させる。 
', $area, $heal);
	}

	protected function init(): void {
		$this->area = new AbilityStatus(12.0);
		$this->heal = new AbilityStatus(5 * 2);
		$this->cooltime = new AbilityStatus(40 * 20);
	}

	public function getHeal(): AbilityStatus {
		return $this->heal;
	}

	protected function onActivate(): ActionResult {

		$circlePar = (new CircleParticle($this->area->get(), $this->area->get()));
		$players = $this->player->getWorld()->getPlayers();
		$target = null;
		$targetHealth = PHP_INT_MAX;
		foreach (EntityUtil::getWithinRange($this->player->getPosition(), $this->area->get()) as $entity) {
			if ($entity instanceof Player) {
				if ($entity !== $this->player) {
					if ($targetHealth > $entity->getHealth()) {
						$target = $entity;
						$targetHealth = $entity->getHealth();
					}
				}
			}
		}


		if ($target instanceof Player) {
			$heal = new EntityRegainHealthEvent($target, $target->getMaxHealth(), EntityRegainHealthEvent::CAUSE_CUSTOM);
			$target->heal($heal);
		}

		ParticleUtil::send(
			$circlePar,
			$players,
			VectorUtil::keepAdd($this->player->getPosition(), 0, 0.25, 0),
			ParticleOption::spawnPacket("minecraft:falling_dust_sand_particle", "")
		);

		return ActionResult::SUCCEEDED();
	}
}
