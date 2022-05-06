<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\job\identity\ability;

use Lyrica0954\StarPvE\job\Ability;
use Lyrica0954\StarPvE\job\Identity;
use Lyrica0954\StarPvE\job\JobIdentity;
use Lyrica0954\StarPvE\job\player\PlayerJob;
use pocketmine\block\Planks;
use pocketmine\player\Player;

abstract class AttachAbilityIdentityBase extends JobIdentity {

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

    public function apply(?Player $player = null): void{
		$this->applyAbility($this->getAttaching());
    }

	abstract public function applyAbility(Ability $ability): void;

    public function reset(?Player $player = null): void{
		$this->resetAbility($this->getAttaching());
    }

	abstract public function resetAbility(Ability $ability): void;
}