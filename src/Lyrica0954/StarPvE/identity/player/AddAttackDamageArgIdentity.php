<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\identity\player;

use Lyrica0954\StarPvE\data\condition\Condition;
use Lyrica0954\StarPvE\identity\Identity;
use Lyrica0954\StarPvE\job\JobIdentity;
use Lyrica0954\StarPvE\job\player\PlayerJob;
use Lyrica0954\StarPvE\utils\BuffUtil;
use Lyrica0954\StarPvE\utils\EntityUtil;
use pocketmine\player\Player;

class AddAttackDamageArgIdentity extends PlayerArgIdentity {

	protected int $add;

	public function __construct(?Condition $condition = null, int $add) {
		parent::__construct($condition);
		$this->add = $add;
	}

	public function getName(): string {
		return "ダメージ増加";
	}

	public function getDescription(): string {
		return "与えるダメージが {$this->add} 増加";
	}

	public function apply(): void {
		if ($this->player !== null) {
			BuffUtil::add($this->player, BuffUtil::BUFF_ATK_DAMAGE, $this->add);
		}
	}

	public function reset(): void {
		if ($this->player !== null) {
			BuffUtil::subtract($this->player, BuffUtil::BUFF_ATK_DAMAGE, $this->add);
		}
	}
}
