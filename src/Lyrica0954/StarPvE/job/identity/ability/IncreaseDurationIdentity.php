<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\job\identity\ability;

use Lyrica0954\StarPvE\data\condition\Condition;
use Lyrica0954\StarPvE\job\Ability;
use Lyrica0954\StarPvE\job\player\PlayerJob;

class IncreaseDurationIdentity extends AttachAbilityIdentityBase {

	protected float $add;

	public function __construct(PlayerJob $playerJob, ?Condition $condition = null, int $attachTo, float $add) {
		parent::__construct($playerJob, $condition, $attachTo);
		$this->add = $add;
	}

	public function getName(): string {
		$attaching = $this->getAttaching();
		$name = $attaching->getCooltimeHandler()->getId();
		return "{$name}効果時間増加";
	}

	public function getDescription(): string {
		$attaching = $this->getAttaching();
		$name = $attaching->getCooltimeHandler()->getId();
		$sec = round($this->add / 20, 1);
		return "{$name}の効果時間が {$sec}秒 増加";
	}

	public function apply(): void {
		$this->getAttaching()->getDuration()->add($this->add);
	}

	public function reset(): void {
		$this->getAttaching()->getDuration()->subtract($this->add);
	}

	public function isAppicableForAbility(Ability $ability) {
		return ($ability->getDuration()->getOriginal() !== 0.0);
	}
}
