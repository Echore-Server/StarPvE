<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\job\identity\ability;

use Lyrica0954\StarPvE\data\condition\Condition;
use Lyrica0954\StarPvE\job\Ability;
use Lyrica0954\StarPvE\job\AbilityStatus;
use Lyrica0954\StarPvE\job\player\PlayerJob;
use Lyrica0954\StarPvE\job\StatusTranslate;

abstract class AttachStatusIdentityBase extends AttachAbilityIdentityBase {


    /**
     * @var int
     */
    protected int $attachStatus;

    public function __construct(PlayerJob $playerJob, ?Condition $condition = null, int $attachTo, int $attachStatus) {
        parent::__construct($playerJob, $condition, $attachTo);
        $this->attachStatus = $attachStatus;
    }

    public function getAttachingStatus(Ability $ability): AbilityStatus {
        $status = match ($this->attachStatus) {
            StatusTranslate::STATUS_DAMAGE => ($ability->getDamage()),
            StatusTranslate::STATUS_AREA => ($ability->getArea()),
            StatusTranslate::STATUS_AMOUNT => ($ability->getAmount()),
            StatusTranslate::STATUS_DURATION => ($ability->getDuration()),
            StatusTranslate::STATUS_PERCENTAGE => ($ability->getPercentage()),
            StatusTranslate::STATUS_SPEED => ($ability->getSpeed()),
            default => (throw new \Exception("unknown status type"))
        };

        return $status;
    }

    public function applyAbility(Ability $ability): void {
        $this->applyStatus($this->getAttachingStatus($ability));
    }

    public function resetAbility(Ability $ability): void {
        $this->resetStatus($this->getAttachingStatus($ability));
    }

    public function isAppicableForAbility(Ability $ability) {
        return ($this->getAttachingStatus($ability))->getOriginal() !== 0.0;
    }

    abstract public function applyStatus(AbilityStatus $status): void;

    abstract public function resetStatus(AbilityStatus $status): void;
}
