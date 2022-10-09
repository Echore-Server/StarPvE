<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\job\player\tank;

use Lyrica0954\StarPvE\job\AbilityStatus;
use Lyrica0954\StarPvE\job\ActionResult;
use Lyrica0954\StarPvE\job\Skill;

class StompSkill extends Skill {

	public function getName(): string {
		return "ストンプ";
	}

	public function getDescription(): string {
		return "";
	}

	protected function init(): void {
		$this->cooltime = new AbilityStatus(20 * 20);
	}

	protected function onActivate(): ActionResult {

		return ActionResult::SUCCEEDED();
	}
}
