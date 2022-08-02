<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\job\identity\ability;

use Lyrica0954\StarPvE\data\condition\Condition;
use Lyrica0954\StarPvE\job\Ability;
use Lyrica0954\StarPvE\job\player\PlayerJob;

class IncreasePercentageIdentity extends AttachAbilityIdentityBase {

	protected float $add;

	public function __construct(PlayerJob $playerJob, ?Condition $condition = null, int $attachTo, float $add) {
		parent::__construct($playerJob, $condition, $attachTo);
		$this->add = $add;
	}

	public function getName(): string {
		$attaching = $this->getAttaching();
		$name = $attaching->getCooltimeHandler()->getId();
		return "{$name}の確率/倍率増加";
	}

	public function getDescription(): string {
		$attaching = $this->getAttaching();
		$name = $attaching->getCooltimeHandler()->getId();
		$percentage = round($this->add * 100);
		return "{$name}の倍率/確率が {$percentage} 増加";
	}

	public function apply(): void {
		$this->getAttaching()->getPercentage()->add($this->add);
	}

	public function reset(): void {
		$this->getAttaching()->getPercentage()->subtract($this->add);
	}

	public function isAppicableForAbility(Ability $ability) {
		return ($ability->getPercentage()->getOriginal() !== 0.0);
	}
}
