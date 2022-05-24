<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\data\condition;

trait ConditionTrait {

	protected ?Condition $condition = null;

	public function getCondition(): ?Condition {
		return $this->condition;
	}

	protected function setCondition(?Condition $condition): void {
		$this->condition = $condition;
	}
}
