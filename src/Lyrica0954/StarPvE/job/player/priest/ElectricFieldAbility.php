<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\job\player\priest;

use Lyrica0954\MagicParticle\CircleParticle;
use Lyrica0954\MagicParticle\ParticleOption;
use Lyrica0954\MagicParticle\SingleParticle;
use Lyrica0954\MagicParticle\utils\MolangUtil;
use Lyrica0954\StarPvE\entity\EntityStateManager;
use Lyrica0954\StarPvE\game\wave\MonsterData;
use Lyrica0954\StarPvE\job\Ability;
use Lyrica0954\StarPvE\job\AbilityStatus;
use Lyrica0954\StarPvE\job\ActionResult;
use Lyrica0954\StarPvE\job\player\AbilitySignal;
use Lyrica0954\StarPvE\job\player\priest\state\EffectAmplificationState;
use Lyrica0954\StarPvE\translate\DescriptionTranslator;
use Lyrica0954\StarPvE\utils\EntityUtil;
use Lyrica0954\StarPvE\utils\ParticleUtil;
use Lyrica0954\StarPvE\utils\PlayerUtil;
use pocketmine\world\Position;

class ElectricFieldAbility extends Ability {

	public function getName(): string {
		return "プリーストフィールド";
	}

	public function getDescription(): string {
		$area = DescriptionTranslator::number($this->area, "m");
		$duration = DescriptionTranslator::second($this->duration);
		return sprintf('§b発動時:§f %1$s 以内の味方に §d効果増幅§f 状態を %2$s 付与する。', $area, $duration);
	}

	protected function init(): void {
		$this->area = new AbilityStatus(6);
		$this->duration = new AbilityStatus(3 * 20);
		$this->cooltime = new AbilityStatus(8 * 20);
	}

	protected function onActivate(): ActionResult {
		foreach (EntityUtil::getWithinRange($this->player->getPosition(), $this->area->get()) as $entity) {
			if (MonsterData::isActiveAlly($entity)) {
				$state = new EffectAmplificationState($entity, 1.5, 2);
				EntityStateManager::startWithDuration($state, (int) $this->duration->get());
			}
		}

		PlayerUtil::broadcastSound($this->player, "beacon.power", 2.4, 0.8);
		$molang = [];
		$molang[] = MolangUtil::variable("lifetime", 1.5);
		$molang[] = MolangUtil::variable("amount", 120);
		$molang[] = MolangUtil::member("color", [
			["r", 1.0],
			["g", 0.0],
			["b", 1.0],
			["a", 0.5]
		]);

		$molang[] = MolangUtil::member("plane", [
			["x", 0.0],
			["y", 1.0],
			["z", 0.0]
		]);


		$molang[] = MolangUtil::variable("radius", $this->area->get());

		ParticleUtil::send(
			new SingleParticle,
			$this->player->getWorld()->getPlayers(),
			Position::fromObject($this->player->getPosition()->add(0, 0.25, 0), $this->player->getWorld()),
			ParticleOption::spawnPacket("starpve:circle", MolangUtil::encode($molang))
		);

		return ActionResult::SUCCEEDED();
	}
}
