<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\identity\player;

use Lyrica0954\StarPvE\data\condition\Condition;
use Lyrica0954\StarPvE\identity\Identity;
use Lyrica0954\StarPvE\job\JobIdentity;
use Lyrica0954\StarPvE\job\player\PlayerJob;
use Lyrica0954\StarPvE\utils\BuffUtil;
use Lyrica0954\StarPvE\utils\EntityUtil;
use Lyrica0954\StarPvE\utils\MathUtil;
use pocketmine\player\Player;

class AttackPercentageArgIdentity extends PlayerArgIdentity {

	protected float $percentage;

	public function __construct(?Condition $condition = null, float $percentage) {
		parent::__construct($condition);
		$this->percentage = $percentage;
	}

	public function getName(): string {
		$tr = MathUtil::translatePercentage($this->percentage, 0.0, 0.0);
		return "ダメージ{$tr[0]}";
	}

	public function getDescription(): string {
		$tr = MathUtil::translatePercentage($this->percentage, 0.0, 0.0);

		$perc = round($tr[2] * 100, 0);
		return "与えるダメージ §c{$tr[1]}{$perc}%";
	}

	public function apply(): void {
		if ($this->player !== null) {
			BuffUtil::add($this->player, BuffUtil::BUFF_ATK_PERCENTAGE, $this->percentage);
		}
	}

	public function reset(): void {
		if ($this->player !== null) {
			BuffUtil::subtract($this->player, BuffUtil::BUFF_ATK_PERCENTAGE, $this->percentage);
		}
	}
}
