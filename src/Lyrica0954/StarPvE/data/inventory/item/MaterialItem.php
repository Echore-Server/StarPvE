<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\data\inventory\item;

use Lyrica0954\StarPvE\data\inventory\InvItem;

class NormalItem extends InvItem {

	protected string $description = "";
	
	public function getName(): string{
		return "Material";
	}

	public function getDescription(): string{
		return $this->description;
	}

	public function setDescription(string $description): void{
		$this->description = $description;
	}
}