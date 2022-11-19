<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\job\player\hawk;

use Lyrica0954\MagicParticle\CircleParticle;
use Lyrica0954\StarPvE\data\condition\Condition;
use Lyrica0954\StarPvE\identity\Identity;
use Lyrica0954\StarPvE\identity\IdentityGroup;
use Lyrica0954\StarPvE\identity\player\SpeedPercentageArgIdentity;
use Lyrica0954\StarPvE\job\Ability;
use Lyrica0954\StarPvE\job\IdentitySpell;
use Lyrica0954\StarPvE\job\player\PlayerJob;
use Lyrica0954\StarPvE\job\player\swordman\ForceFieldSkill;
use Lyrica0954\StarPvE\job\Skill;

class Hawk extends PlayerJob {

	protected function getInitialIdentityGroup(): IdentityGroup {
		$g = new IdentityGroup();
		return $g;
	}

	protected function getInitialAbility(): Ability {
		return new AssasinAbility($this);
	}

	protected function getInitialSkill(): Skill {
		return new FlightSkill($this);
	}

	public function getName(): string {
		return "Hawk";
	}

	public function getDescription(): string {
		return
			"§7- §l§9防衛§r
常に §d心得§f , §dわしづかみ§f スペルを所持

移動速度が早く、敵を翻弄することができる職業。";
	}

	public function getSelectableCondition(): ?Condition {
		return null;
	}

	protected function init(): void {
		$this->addSpell(
			(new IdentitySpell($this, "心得"))
				->addIdentity(new SpeedPercentageArgIdentity(
					null,
					1.35
				))

		);

		$this->addSpell(new GrabSpell($this));
	}
}
