<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\job\identity\ability;

use Lyrica0954\StarPvE\data\condition\Condition;
use Lyrica0954\StarPvE\job\Ability;
use Lyrica0954\StarPvE\job\JobIdentity;
use Lyrica0954\StarPvE\job\player\PlayerJob;

class AbilityValueSignalIdentity extends AttachAbilityIdentityBase {

	protected string $description;

	protected int $id;

	protected int $adds;

	public function __construct(PlayerJob $playerJob, ?Condition $condition, int $attachTo, int $id, int $adds, string $description) {
		parent::__construct($playerJob, $condition, $attachTo);
		$this->description = $description;
		$this->id = $id;
		$this->adds = $adds;
	}

	public function getName(): string {
		return $this->getAttachName() . "能力追加";
	}

	public function getDescription(): string {
		$op = "";
		if ($this->adds >= 0) {
			$op = "+";
		}
		return $this->description . " §c{$op}{$this->adds}";
	}

	public function applyAbility(Ability $ability): void {
		$ability->getSignal()->add($this->id, $this->adds);
	}

	public function resetAbility(Ability $ability): void {
		$ability->getSignal()->add($this->id, -$this->adds);
	}
}
