<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\job\identity\ability;

use Lyrica0954\StarPvE\data\condition\Condition;
use Lyrica0954\StarPvE\job\Ability;
use Lyrica0954\StarPvE\job\JobIdentity;
use Lyrica0954\StarPvE\job\player\PlayerJob;

class AbilitySignalIdentity extends AttachAbilityIdentityBase {

	protected string $description;

	protected int $id;

	public function __construct(PlayerJob $playerJob, ?Condition $condition, int $attachTo, int $id, string $description) {
		parent::__construct($playerJob, $condition, $attachTo);
		$this->description = $description;
		$this->id = $id;
	}

	public function getName(): string {
		return "能力追加";
	}

	public function getDescription(): string {
		return $this->description;
	}

	public function applyAbility(Ability $ability): void {
		$ability->getSignal()->set($this->id);
	}

	public function resetAbility(Ability $ability): void {
		$ability->getSignal()->set($this->id, false);
	}
}
