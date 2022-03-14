<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\job\identity\ability;

use Lyrica0954\StarPvE\job\Ability;
use Lyrica0954\StarPvE\job\Identity;
use Lyrica0954\StarPvE\job\player\PlayerJob;

abstract class AttachAbilityIdentityBase extends Identity {

	const ATTACH_ABILITY = 0;
	const ATTACH_SKILL = 1;

	protected int $attachTo;

	public function __construct(PlayerJob $playerJob, int $attachTo){
		parent::__construct($playerJob);
		$this->attachTo = $attachTo;
	}

	public function getAttaching(): Ability{
		switch($this->attachTo){
			case (self::ATTACH_ABILITY):
				return $this->playerJob->getAbility();
				break;
			case (self::ATTACH_SKILL):
				return $this->playerJob->getSkill();
				break;
		}
	}

    public function apply(): void{
		$this->applyAbility($this->getAttaching());
    }

	abstract public function applyAbility(Ability $ability): void;

    public function reset(): void{
		$this->resetAbility($this->getAttaching());
    }

	abstract public function resetAbility(Ability $ability): void;
}