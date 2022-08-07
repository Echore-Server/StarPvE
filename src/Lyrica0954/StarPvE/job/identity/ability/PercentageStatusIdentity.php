<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\job\identity\ability;

use Lyrica0954\StarPvE\data\condition\Condition;
use Lyrica0954\StarPvE\job\Ability;
use Lyrica0954\StarPvE\job\AbilityStatus;
use Lyrica0954\StarPvE\job\player\PlayerJob;
use Lyrica0954\StarPvE\job\StatusTranslate;

class PercentageStatusIdentity extends AttachStatusIdentityBase {

    protected float $percentage;

    public function __construct(PlayerJob $playerJob, ?Condition $condition = null, int $attachTo, int $attachStatus, float $percentage) {
        parent::__construct($playerJob, $condition, $attachTo, $attachStatus);
        $this->percentage = $percentage;
    }

    public function getName(): string {
        $attaching = $this->getAttaching();
        $name = $attaching->getCooltimeHandler()->getId();
        $statusName = StatusTranslate::translate($this->attachStatus);
        return "{$name}の{$statusName}増加";
    }

    public function getDescription(): string {
        $attaching = $this->getAttaching();
        $name = $attaching->getCooltimeHandler()->getId();
        $statusName = StatusTranslate::translate($this->attachStatus);
        $perc = round(($this->percentage - 1.0) * 100);
        return "{$name}の{$statusName} §c+{$perc}%§f";
    }

    public function applyStatus(AbilityStatus $status): void {
        $status->multiply($this->percentage);
    }

    public function resetStatus(AbilityStatus $status): void {
        $status->divide($this->percentage);
    }
}
