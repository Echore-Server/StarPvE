<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\job\identity\ability;

use Lyrica0954\StarPvE\job\Ability;
use Lyrica0954\StarPvE\job\player\PlayerJob;

class IncreaseDamageIdentity extends AttachAbilityIdentityBase {

	protected float $add;

	public function __construct(PlayerJob $playerJob, int $attachTo, float $add){
		parent::__construct($playerJob, $attachTo);
		$this->add = $add;
	}

	public function getName(): string{
		$attaching = $this->getAttaching();
		$name = $attaching->getCooltimeHandler()->getId();
		return "量/回数が{$name}増加";
	}

	public function getDescription(): string{
		$attaching = $this->getAttaching();
		$name = $attaching->getCooltimeHandler()->getId();
		return "{$name}の量/回数が {$this->add} 増加";
	}

	public function applyAbility(Ability $ability): void{
		$ability->getAmount()->add($this->add);
	}

	public function resetAbility(Ability $ability): void{
		$ability->getAmount()->subtract($this->add);
	}
}