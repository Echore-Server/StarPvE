<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\job\player\healer;

use Lyrica0954\MagicParticle\CircleParticle;
use Lyrica0954\MagicParticle\LineParticle;
use Lyrica0954\MagicParticle\ParticleOption;
use Lyrica0954\MagicParticle\SingleParticle;
use Lyrica0954\StarPvE\job\Ability;
use Lyrica0954\StarPvE\job\AbilityStatus;
use Lyrica0954\StarPvE\job\ActionResult;
use Lyrica0954\StarPvE\StarPvE;
use Lyrica0954\StarPvE\translate\DescriptionTranslator;
use Lyrica0954\StarPvE\utils\EffectGroup;
use Lyrica0954\StarPvE\utils\EntityUtil;
use Lyrica0954\StarPvE\utils\ParticleUtil;
use Lyrica0954\StarPvE\utils\PlayerUtil;
use Lyrica0954\StarPvE\utils\VectorUtil;
use pocketmine\entity\effect\Effect;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\event\entity\EntityRegainHealthEvent;
use pocketmine\player\Player;

class HarmonyAbility extends Ability {

	public function getName(): string {
		return "ハーモニー";
	}

	public function getDescription(): string {
		$area = DescriptionTranslator::number($this->area, "m");
		$heal = DescriptionTranslator::health($this->damage);
		$duration = DescriptionTranslator::second($this->duration);
		return
			sprintf('§b発動時:§f %1$s 以内の味方(自分以外)の体力を %2$s 回復させ、 %3$s 秒無敵にする。', $area, $heal, $duration);
	}

	protected function init(): void {
		$this->area = new AbilityStatus(12.0);
		$this->duration = new AbilityStatus(0.75 * 20);
		$this->damage = new AbilityStatus(2);
		$this->cooltime = new AbilityStatus(5 * 20);
	}

	protected function onActivate(): ActionResult {

		$par = (new SingleParticle);
		$circlePar = (new CircleParticle($this->area->get(), 12));
		$players = $this->player->getWorld()->getPlayers();
		foreach (EntityUtil::getWithinRange($this->player->getPosition(), $this->area->get()) as $entity) {
			if ($entity instanceof Player) {
				if ($entity !== $this->player) {
					PlayerUtil::broadcastSound($entity, "mob.guardian.hit", 1.2, 0.7);
					$source = new EntityRegainHealthEvent($entity, $this->damage->get(), EntityRegainHealthEvent::CAUSE_CUSTOM);
					$entity->heal($source);

					$effect = new EffectInstance(VanillaEffects::RESISTANCE(), (int) $this->duration->get(), 255, false);
					$entity->getEffects()->add($effect);
				}
			}
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
