<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\job\player;

class AbilitySignal {

	/**
	 * @var bool[]
	 */
	protected array $list;

	/**
	 * @var int[]
	 */
	protected array $values;

	public function __construct() {
		$this->list = [];
		$this->values = [];
	}

	/**
	 * @return bool[]
	 */
	public function getAll(): array {
		return $this->list;
	}

	public function has(int $id): bool {
		return $this->list[$id] ?? false;
	}

	public function set(int $id, bool $value = true): void {
		$this->list[$id] = $value;
	}

	public function add(int $id, int $value = 1): void {
		$this->values[$id] ?? $this->values[$id] = 0;

		$this->values[$id] += $value;
	}

	public function get(int $id): int {
		return $this->values[$id] ?? 0;
	}
}
