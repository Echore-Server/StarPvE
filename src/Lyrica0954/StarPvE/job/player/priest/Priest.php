<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\job\player\priest;

use Lyrica0954\StarPvE\data\condition\Condition;
use Lyrica0954\StarPvE\identity\IdentityGroup;
use Lyrica0954\StarPvE\job\Ability;
use Lyrica0954\StarPvE\job\player\PlayerJob;
use Lyrica0954\StarPvE\job\player\swordman\ForceFieldSkill;
use Lyrica0954\StarPvE\job\Skill;

class Priest extends PlayerJob {

	public function getName(): string {
		return "Priest";
	}

	public function getDescription(): string {
		return "§c§l製作途中なので選択しないでください。";
	}

	public function getSelectableCondition(): ?Condition {
		return null;
	}

	protected function getInitialAbility(): Ability {
		return new ElectricFieldAbility($this);
	}

	protected function getInitialSkill(): Skill {
		return new ForceFieldSkill($this);
	}

	protected function getInitialIdentityGroup(): IdentityGroup {
		return new IdentityGroup;
	}
}
