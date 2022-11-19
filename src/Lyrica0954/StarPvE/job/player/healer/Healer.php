<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\job\player\healer;

use Lyrica0954\MagicParticle\CircleParticle;
use Lyrica0954\StarPvE\data\condition\Condition;
use Lyrica0954\StarPvE\identity\Identity;
use Lyrica0954\StarPvE\identity\IdentityGroup;
use Lyrica0954\StarPvE\identity\player\AddMaxHealthArgIdentity;
use Lyrica0954\StarPvE\identity\player\AddMaxHealthIdentity;
use Lyrica0954\StarPvE\job\Ability;
use Lyrica0954\StarPvE\job\AlwaysAbility;
use Lyrica0954\StarPvE\job\identity\ability\AttachAbilityIdentityBase;
use Lyrica0954\StarPvE\job\identity\ability\IncreaseAreaIdentity;
use Lyrica0954\StarPvE\job\identity\ability\IncreaseStatusIdentity;
use Lyrica0954\StarPvE\job\player\healer\identity\FastFeedIdentity;
use Lyrica0954\StarPvE\job\player\PlayerJob;
use Lyrica0954\StarPvE\job\Skill;
use Lyrica0954\StarPvE\job\StatusTranslate;
use Lyrica0954\StarPvE\StarPvE;
use Lyrica0954\StarPvE\utils\EntityUtil;
use Lyrica0954\StarPvE\utils\VectorUtil;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\Listener;
use pocketmine\player\Player;
use pocketmine\Server;

class Healer extends PlayerJob {

	protected function getInitialIdentityGroup(): IdentityGroup {
		$g = new IdentityGroup();
		$list = [
			new AddMaxHealthArgIdentity(null, 2),
			new FastFeedIdentity(null, 30)
		];
		$g->addAllSafe($list);
		return $g;
	}

	protected function getInitialAbility(): Ability {
		return new HarmonyAbility($this);
	}

	protected function getInitialSkill(): Skill {
		return new ConcordeSkill($this);
	}

	public function getName(): string {
		return "Healer";
	}

	public function getDescription(): string {
		return
			"§7- §l§a支援[♡]§r

味方を回復できるヒーラー。
味方を支援したり、回復させたりすることができる。";
	}

	public function getSelectableCondition(): ?Condition {
		return null;
	}
}
