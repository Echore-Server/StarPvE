<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\job\player\magician;

use Lyrica0954\StarPvE\data\condition\Condition;
use Lyrica0954\StarPvE\data\condition\LevelCondition;
use Lyrica0954\StarPvE\identity\IdentityGroup;
use Lyrica0954\StarPvE\identity\player\SpeedPercentageArgIdentity;
use Lyrica0954\StarPvE\job\Ability;
use Lyrica0954\StarPvE\job\identity\ability\AttachAbilityIdentityBase;
use Lyrica0954\StarPvE\job\identity\ability\PercentageStatusIdentity;
use Lyrica0954\StarPvE\job\IdentitySpell;
use Lyrica0954\StarPvE\job\player\PlayerJob;
use Lyrica0954\StarPvE\job\Skill;
use Lyrica0954\StarPvE\job\StatusTranslate;
use Lyrica0954\StarPvE\translate\DescriptionTranslator;

class Magician extends PlayerJob {

	protected function getInitialAbility(): Ability {
		return new ThunderboltAbility($this);
	}

	protected function getInitialSkill(): Skill {
		return new PowerBoostSkill($this);
	}

	protected function getInitialIdentityGroup(): IdentityGroup {
		$idt = new IdentityGroup();
		$list = [
			new SpeedPercentageArgIdentity(null, 0.75)
		];
		$idt->addAll($list);
		return $idt;
	}

	protected function init(): void {
		$this->defaultSpells = [
			new EnergyBurstSpell($this),
			(new IdentitySpell($this, "1億アンペア"))
				->addIdentity(new PercentageStatusIdentity(
					$this,
					null,
					AttachAbilityIdentityBase::ATTACH_ABILITY,
					StatusTranslate::STATUS_DURATION,
					2.25
				))
				->addIdentity(new PercentageStatusIdentity(
					$this,
					null,
					AttachAbilityIdentityBase::ATTACH_ABILITY,
					StatusTranslate::STATUS_COOLTIME,
					1.15
				))
				->addIdentity(new PercentageStatusIdentity(
					$this,
					null,
					AttachAbilityIdentityBase::ATTACH_SKILL,
					StatusTranslate::STATUS_DURATION,
					1.5
				))
		];
	}

	public function getName(): string {
		return "Magician";
	}

	public function getDescription(): string {
		return
			"§7- §l§c戦闘§r

雷を操るマジシャン。遠距離から敵を攻撃したり、集団の敵を殲滅するのが得意な職業。
全ての職業の中でもかなり秒間攻撃力が高いこの職業だが、
アビリティやスキルなどで、敵をノックバックさせることができないため
敵に狙われると少し危ない。";
	}

	public function getSelectableCondition(): ?Condition {
		return null;
	}
}
