<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\job\player\shaman;

use Lyrica0954\StarPvE\data\condition\Condition;
use Lyrica0954\StarPvE\job\Ability;
use Lyrica0954\StarPvE\job\AlwaysAbility;
use Lyrica0954\StarPvE\job\IdentityGroup;
use Lyrica0954\StarPvE\job\player\PlayerJob;
use Lyrica0954\StarPvE\job\player\swordman\ForceFieldSkill;
use Lyrica0954\StarPvE\job\Skill;
use pocketmine\event\Listener;

class Shaman extends PlayerJob implements Listener {

	public function getName(): string{
		return "Shaman";
	}

    public function getDescription(): string{
        return 
"§7- §l§c戦闘§r

範囲攻撃を得意とする職業で、攻撃もかなり強力だが
アビリティなどが特殊で扱いが難しく上級者向け。";
    }

	protected function getInitialAbility(): Ability{
		return new DownPulseAbility($this);
	}

	protected function getInitialSkill(): Skill{
		return new DeathPulseSkill($this);
	}

	protected function getInitialIdentityGroup(): IdentityGroup{
		return new IdentityGroup($this);
	}

	public function getSelectableCondition(): ?Condition{
		return null;
	}

	# collapse 	dig.vines
}