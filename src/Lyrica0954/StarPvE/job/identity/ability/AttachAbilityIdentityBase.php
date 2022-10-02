<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\job\identity\ability;

use Lyrica0954\StarPvE\data\condition\Condition;
use Lyrica0954\StarPvE\job\Ability;
use Lyrica0954\StarPvE\job\AbilitySpell;
use Lyrica0954\StarPvE\job\Identity;
use Lyrica0954\StarPvE\job\JobIdentity;
use Lyrica0954\StarPvE\job\player\PlayerJob;
use Lyrica0954\StarPvE\job\Spell;
use pocketmine\block\Planks;
use pocketmine\player\Player;

abstract class AttachAbilityIdentityBase extends JobIdentity {

	const ATTACH_ABILITY = 0;
	const ATTACH_SKILL = 1;
	const ATTACH_SPELL = 2;

	protected int $attachTo;

	public function __construct(PlayerJob $playerJob, ?Condition $condition = null, int $attachTo) {
		parent::__construct($playerJob, $condition);
		$this->attachTo = $attachTo;
	}

	/**
	 * @return Ability[]
	 */
	public function getAttaching(): array {
		switch ($this->attachTo) {
			case (self::ATTACH_ABILITY):
				return [$this->playerJob->getAbility()];
				break;
			case (self::ATTACH_SKILL):
				return [$this->playerJob->getSkill()];
				break;
			case (self::ATTACH_SPELL):
				return array_filter($this->playerJob->getSpells(), function (Spell $item): bool {
					return $item instanceof AbilitySpell;
				});
				break;
		}
	}

	public function getAttachName(): string {
		return match ($this->attachTo) {
			self::ATTACH_ABILITY => "アビリティ",
			self::ATTACH_SKILL => "スキル",
			self::ATTACH_SPELL => "スペル",
			default => "unknown"
		};
	}

	public function apply(): void {
		foreach ($this->getAttaching() as $ability) {
			$this->applyAbility($ability);
		}
	}

	public function reset(): void {
		foreach ($this->getAttaching() as $ability) {
			$this->resetAbility($ability);
		}
	}

	abstract public function applyAbility(Ability $ability): void;

	abstract public function resetAbility(Ability $ability): void;

	public function setAttach(int $attachTo): void {
		$this->attachTo = $attachTo;
	}

	public function isApplicableForAbility(Ability $ability) {
		return true;
	}
}
