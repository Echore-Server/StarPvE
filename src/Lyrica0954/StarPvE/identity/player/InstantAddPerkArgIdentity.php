<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\identity\player;

use Lyrica0954\StarPvE\data\condition\Condition;
use Lyrica0954\StarPvE\StarPvE;
use Lyrica0954\StarPvE\utils\MathUtil;

class InstantAddPerkArgIdentity extends PlayerArgIdentity {

	protected int $add;

	public function __construct(?Condition $condition = null, int $add) {
		parent::__construct($condition);
		$this->add = $add;
	}

	public function getName(): string {
		$tr = MathUtil::translateAdd($this->add);
		return "所持パーク数{$tr[0]}";
	}

	public function getDescription(): string {
		$tr = MathUtil::translateAdd($this->add);
		return "所持パーク数 §c{$tr[1]}{$tr[2]}";
	}

	public function apply(): void {
		if ($this->player !== null) {
			$gamePlayer = StarPvE::getInstance()->getGamePlayerManager()?->getGamePlayer($this->player);
			if ($gamePlayer !== null) {
				$gamePlayer->setPerkAvailable($gamePlayer->getPerkAvailable() + $this->add);
			}
		}
	}

	public function reset(): void {
	}
}
