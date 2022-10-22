<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\job\player\warrior;

use Lyrica0954\StarPvE\data\condition\Condition;
use Lyrica0954\StarPvE\data\condition\FalseCondition;
use Lyrica0954\StarPvE\data\condition\LevelCondition;
use Lyrica0954\StarPvE\identity\IdentityGroup;
use Lyrica0954\StarPvE\identity\player\SpeedPercentageArgIdentity;
use Lyrica0954\StarPvE\job\Ability;
use Lyrica0954\StarPvE\job\player\PlayerJob;
use Lyrica0954\StarPvE\job\player\swordman\ForceFieldSkill;
use Lyrica0954\StarPvE\job\Skill;
use Lyrica0954\StarPvE\translate\DescriptionTranslator;

class Warrior extends PlayerJob {

	protected function getInitialAbility(): Ability {
		return new AxeAbility($this);
	}

	protected function getInitialSkill(): Skill {
		return new ForceFieldSkill($this);
	}

	protected function getInitialIdentityGroup(): IdentityGroup {
		$idt = new IdentityGroup();
		$list = [
			new SpeedPercentageArgIdentity(null, 0.75)
		];
		$idt->addAll($list);
		return $idt;
	}

	public function getName(): string {
		return "Warrior";
	}

	public function getDescription(): string {
		return
			"§7- §l§c戦闘§r
";
	}

	public function getSelectableCondition(): ?Condition {
		return null;
	}
}
