<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\utils;

use pocketmine\entity\effect\Effect;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\entity\Entity;
use pocketmine\entity\Living;

class EffectGroup {

	/**
	 * @var EffectInstance[]
	 */
	private array $effects;

	public function __construct(EffectInstance... $effects){
		$this->effects = $effects;
	}

	public function add(EffectInstance $effect): void{
		$this->effects[] = $effect;
	}

	/**
	 * @return EffectInstance[]
	 */
	public function getAll(): array{
		return $this->effects;
	}

	public function apply(Living $living): void{
		$ef = $living->getEffects();
		foreach($this->effects as $effect){
			$ef->add(clone $effect);
		}
	}
}