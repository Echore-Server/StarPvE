<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\data\inventory\item;

class MaterialItem extends InvItem {

	protected string $description = "";

	public function getName(): string {
		return "Material";
	}

	public function getDescription(): string {
		return $this->description;
	}

	public function setDescription(string $description): void {
		$this->description = $description;
	}

	public function getMaxStackSize(): int {
		return 128;
	}
}
