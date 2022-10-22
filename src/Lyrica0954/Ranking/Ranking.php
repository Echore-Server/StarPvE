<?php

declare(strict_types=1);

namespace Lyrica0954\Ranking;

use Closure;

class Ranking {

	protected string $displayName;

	protected string $name;

	protected float|int $value;

	public static function manualSort(): Closure {
		return function (Ranking $a, Ranking $b): int {
			return $b->compare($a);
		};
	}

	public function __construct(string $name) {
		$this->name = $name;
		$this->displayName = $name;
		$this->value = 0.0;
	}

	public function isHigher(Ranking $other): bool {
		return $other->getValue() > $this->getValue();
	}

	public function isLower(Ranking $other): bool {
		return $other->getValue() < $this->getValue();
	}

	public function compare(Ranking $other): int {
		$c = 0;

		if ($this->isHigher($other)) {
			$c = -1;
		} elseif ($this->isLower($other)) {
			$c = 1;
		}

		return $c;
	}

	/**
	 * Get the value of name
	 *
	 * @return string
	 */
	public function getName(): string {
		return $this->name;
	}

	/**
	 * Get the value of displayName
	 *
	 * @return string
	 */
	public function getDisplayName(): string {
		return $this->displayName;
	}

	/**
	 * Set the value of displayName
	 *
	 * @param string $displayName
	 *
	 * @return self
	 */
	public function setDisplayName(string $displayName): self {
		$this->displayName = $displayName;

		return $this;
	}

	/**
	 * Get the value of value
	 *
	 * @return float|int
	 */
	public function getValue(): float|int {
		return $this->value;
	}

	/**
	 * Set the value of value
	 *
	 * @param float|int $value
	 *
	 * @return self
	 */
	public function setValue(float|int $value): self {
		$this->value = $value;

		return $this;
	}
}
