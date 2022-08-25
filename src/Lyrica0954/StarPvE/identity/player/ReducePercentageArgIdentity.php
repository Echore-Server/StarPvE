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

class ReducePercentageArgIdentity extends PlayerArgIdentity {

	protected float $percentage;

	public function __construct(?Condition $condition = null, float $percentage) {
		parent::__construct($condition);
		$this->percentage = $percentage;
	}

	public function getName(): string {
		return "防御力上昇";
	}

	public function getDescription(): string {
		$p = $this->percentage * 100;
		return "受けるダメージ §9-{$p}%";
	}

	public function apply(): void {
		if ($this->player !== null) {
			BuffUtil::add($this->player, BuffUtil::BUFF_DMG_REDUCTION_PERC, $this->percentage);
		}
	}

	public function reset(): void {
		if ($this->player !== null) {
			BuffUtil::subtract($this->player, BuffUtil::BUFF_DMG_REDUCTION_PERC, $this->percentage);
		}
	}
}
