<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\job\identity\ability;

use Lyrica0954\StarPvE\data\condition\Condition;
use Lyrica0954\StarPvE\job\Ability;
use Lyrica0954\StarPvE\job\AbilityStatus;
use Lyrica0954\StarPvE\job\player\PlayerJob;
use Lyrica0954\StarPvE\job\StatusTranslate;
use Lyrica0954\StarPvE\utils\MathUtil;

class IncreaseStatusIdentity extends AttachStatusIdentityBase {

	protected float $add;

	public function __construct(PlayerJob $playerJob, ?Condition $condition = null, int $attachTo, int $attachStatus, float $add) {
		parent::__construct($playerJob, $condition, $attachTo, $attachStatus);
		$this->add = $add;
	}

	public function getName(): string {
		$name = $this->getAttachName();
		$statusName = StatusTranslate::translate($this->attachStatus);
		$op = MathUtil::translateAdd($this->add)[0];
		return "{$name}の{$statusName}{$op}";
	}

	public function getDescription(): string {
		$name = $this->getAttachName();
		$statusName = StatusTranslate::translate($this->attachStatus);
		$tr = MathUtil::translateAdd($this->add);
		return "{$name}の{$statusName} §c{$tr[0]}{$tr[2]}§f";
	}

	public function applyStatus(AbilityStatus $status): void {
		$status->add($this->add);
	}

	public function resetStatus(AbilityStatus $status): void {
		$status->subtract($this->add);
	}
}
