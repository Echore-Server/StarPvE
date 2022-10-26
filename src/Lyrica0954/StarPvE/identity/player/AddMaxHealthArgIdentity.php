<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\identity\player;

use Lyrica0954\StarPvE\data\condition\Condition;
use Lyrica0954\StarPvE\identity\Identity;
use Lyrica0954\StarPvE\job\JobIdentity;
use Lyrica0954\StarPvE\job\player\PlayerJob;
use Lyrica0954\StarPvE\utils\EntityUtil;
use Lyrica0954\StarPvE\utils\MathUtil;
use pocketmine\player\Player;

class AddMaxHealthArgIdentity extends PlayerArgIdentity {

	protected int $add;

	public function __construct(?Condition $condition = null, int $add) {
		parent::__construct($condition);
		$this->add = $add;
	}

	public function getName(): string {
		$tr = MathUtil::translateAdd($this->add);
		return "最大HP{$tr[0]}";
	}

	public function getDescription(): string {
		$tr = MathUtil::translateAdd($this->add);
		return "最大HP §c{$tr[1]}{$tr[2]}";
	}

	public function apply(): void {
		if ($this->player !== null) {
			EntityUtil::addMaxHealthSynchronously($this->player, $this->add);
		}
	}

	public function reset(): void {
		if ($this->player !== null) {
			EntityUtil::addMaxHealthSynchronously($this->player, -$this->add);
		}
	}
}
