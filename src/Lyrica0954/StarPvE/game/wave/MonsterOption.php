<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\game\wave;

use Lyrica0954\StarPvE\utils\ArmorSet;
use pocketmine\item\Item;

class MonsterOption {

	protected string $class;

	protected MonsterAttribute $attribute;

	protected ArmorSet $equipment;

	/**
	 * @var Item[]
	 */
	protected array $drop;

	protected float $exp;

	public function __construct(
		string $class,
		MonsterAttribute $attribute,
		ArmorSet $equipment,
		array $drop,
		float $exp,
	) {
		$this->class = $class;
		$this->attribute = $attribute;
		$this->equipment = $equipment;
		$this->drop = $drop;
		$this->exp = $exp;
	}

	public function getClass(): string {
		return $this->class;
	}

	public function getAttribute(): MonsterAttribute {
		return $this->attribute;
	}

	public function getEquipment(): ArmorSet {
		return $this->equipment;
	}

	/**
	 * @return Item[]
	 */
	public function getDrop(): array {
		return $this->drop;
	}

	public function getExp(): float {
		return $this->exp;
	}


	public function __clone() {
		$this->attribute = clone $this->attribute;
		$this->equipment = clone $this->equipment;
	}
}
