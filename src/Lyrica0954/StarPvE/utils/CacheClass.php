<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\utils;

class CacheClass {

	protected string $class;

	/**
	 * @var mixed[]
	 */
	protected array $args;

	public function __construct(string $class, ...$args) {
		$this->class = $class;
		$this->args = $args;
	}

	public function call(): mixed {
		return (new $this->class)(...$this->args);
	}
}
