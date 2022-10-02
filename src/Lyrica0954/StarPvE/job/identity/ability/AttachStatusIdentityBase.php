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

	/**
	 * @param Ability $ability
	 * 
	 * @return AbilityStatus[]
	 */
	public function getAttachingStatus(Ability $ability): array {
		return $ability->getStatusList($this->attachStatus) ?? throw new \Exception("unknown status type");
	}

	public function applyAbility(Ability $ability): void {
		foreach ($this->getAttachingStatus($ability) as $status) {
			$this->applyStatus($status);
		}
	}

	public function resetAbility(Ability $ability): void {
		foreach ($this->getAttachingStatus($ability) as $status) {
			$this->resetStatus($status);
		}
	}

	public function isApplicableForAbility(Ability $ability) {
		$found = false;
		foreach ($this->getAttachingStatus($ability) as $status) {
			if ($status->getOriginal() !== 0.0) {
				$found = true;
				break;
			}
		}

		return $found;
	}

	abstract public function applyStatus(AbilityStatus $status): void;

	abstract public function resetStatus(AbilityStatus $status): void;
}
