<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\job\player;

class AbilitySignal {

	/**
	 * @var bool[]
	 */
	protected array $list;

	public function __construct() {
		$this->list = [];
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
}
