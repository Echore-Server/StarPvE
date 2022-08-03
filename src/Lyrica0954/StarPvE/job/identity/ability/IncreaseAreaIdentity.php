<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\job\identity\ability;

use Lyrica0954\StarPvE\data\condition\Condition;
use Lyrica0954\StarPvE\job\Ability;
use Lyrica0954\StarPvE\job\player\PlayerJob;

class IncreaseAreaIdentity extends AttachAbilityIdentityBase {

	protected float $add;

	public function __construct(PlayerJob $playerJob, ?Condition $condition = null, int $attachTo, float $add) {
		parent::__construct($playerJob, $condition, $attachTo);
		$this->add = $add;
	}

	public function getName(): string {
		$attaching = $this->getAttaching();
		$name = $attaching->getCooltimeHandler()->getId();
		return "{$name}範囲増加";
	}

	public function getDescription(): string {
		$attaching = $this->getAttaching();
		$name = $attaching->getCooltimeHandler()->getId();
		return "{$name}の範囲が {$this->add} 増加";
	}

	public function applyAbility(Ability $ability): void {
		$ability->getArea()->add($this->add);
	}

	public function resetAbility(Ability $ability): void {
		$ability->getArea()->subtract($this->add);
	}

	public function isAppicableForAbility(Ability $ability) {
		return ($ability->getArea()->getOriginal() !== 0.0);
	}
}
