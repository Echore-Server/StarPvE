<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\job\player\tank;

use Lyrica0954\StarPvE\job\Ability;
use Lyrica0954\StarPvE\job\AbilityStatus;
use Lyrica0954\StarPvE\job\ActionResult;
use Lyrica0954\StarPvE\translate\DescriptionTranslator;
use Lyrica0954\StarPvE\utils\EffectGroup;
use Lyrica0954\StarPvE\utils\PlayerUtil;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\entity\effect\VanillaEffects;

class RegrowthAbility extends Ability {

	protected EffectGroup $effects;

	public function getName(): string {
		return "リグロウス";
	}

	public function getDescription(): string {
		$effects = DescriptionTranslator::effectGroup($this->effects);
		return sprintf('発動時 %1$s を獲得する。', $effects);
	}

	protected function init(): void {
		$this->effects = new EffectGroup(
			new EffectInstance(VanillaEffects::REGENERATION(), (10 * 20), 0),
			new EffectInstance(VanillaEffects::ABSORPTION(), (10 * 20), 2)
		);

		$this->cooltime = new AbilityStatus(16 * 20);
	}

	protected function onActivate(): ActionResult {
		$this->effects->apply($this->player);

		PlayerUtil::playSound($this->player, "respawn_anchor.deplete", 0.75, 1.0);

		return ActionResult::SUCCEEDED();
	}
}
