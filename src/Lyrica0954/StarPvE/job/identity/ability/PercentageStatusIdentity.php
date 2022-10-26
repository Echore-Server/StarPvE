<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\job\identity\ability;

use Lyrica0954\StarPvE\data\condition\Condition;
use Lyrica0954\StarPvE\job\Ability;
use Lyrica0954\StarPvE\job\AbilityStatus;
use Lyrica0954\StarPvE\job\player\PlayerJob;
use Lyrica0954\StarPvE\job\StatusTranslate;
use Lyrica0954\StarPvE\utils\MathUtil;

class PercentageStatusIdentity extends AttachStatusIdentityBase {

	protected float $percentage;

	public function __construct(PlayerJob $playerJob, ?Condition $condition = null, int $attachTo, int $attachStatus, float $percentage) {
		parent::__construct($playerJob, $condition, $attachTo, $attachStatus);
		$this->percentage = $percentage;
	}

	public function getName(): string {
		$name = $this->getAttachName();
		$statusName = StatusTranslate::translate($this->attachStatus);
		$op = MathUtil::translateAdd($this->percentage)[0];
		return "{$name}の{$statusName}{$op}";
	}

	public function getDescription(): string {
		$name = $this->getAttachName();
		$statusName = StatusTranslate::translate($this->attachStatus);
		$tr = MathUtil::translatePercentage($this->percentage);

		$perc = round($tr[2] * 100, 0);
		return "{$name}の{$statusName} §c{$tr[1]}{$perc}%§f";
	}

	public function applyStatus(AbilityStatus $status): void {
		$status->multiply($this->percentage);
	}

	public function resetStatus(AbilityStatus $status): void {
		$status->divide($this->percentage);
	}
}
