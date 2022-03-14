<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\job;

class AbilityStatus {

	const MODIFIER_ADD = 0;
	const MODIFIER_MULTIPLY = 1;
	const MODIFIER_CUSTOM = 2;

	private float $original;
	private array $modifiers;

	public function __construct(float $original){
		$this->original = $original;
		$this->modifiers = [
		];
	}

	public function get(): float{
		return ($this->original + array_sum($this->modifiers));
	}

	public function getDiff(): float{
		return array_sum($this->modifiers);
	}

	public function getModifier(int $modifier): float {
		return $this->modifiers[$modifier] ?? 0.0;
	}

	public function getOriginal(): float {
		return $this->original;
	}
	
	public function add(float $add): void{
		if (!isset($this->modifiers[self::MODIFIER_ADD])){
			$this->modifiers[self::MODIFIER_ADD] = 0.0;
		}
		$this->modifiers[self::MODIFIER_ADD] += $add;
	}

	public function subtract(float $sub): void{
		$this->add(-$sub);
	}

	public function multiply(float $m): void{
		$add = ($this->original * ($m - 1.0));
		
		if (!isset($this->modifiers[self::MODIFIER_MULTIPLY])){
			$this->modifiers[self::MODIFIER_MULTIPLY] = 0.0;
		}
		$this->modifiers[self::MODIFIER_MULTIPLY] += $add;
	}

	public function divide(float $d): void{
		$m = (1 / $d);
		$this->multiply($m);
	}
}