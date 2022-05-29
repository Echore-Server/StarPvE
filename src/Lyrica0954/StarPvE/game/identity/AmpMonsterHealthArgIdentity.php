<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\game\identity;

use Lyrica0954\StarPvE\event\game\wave\WaveMonsterSpawnEvent;
use Lyrica0954\StarPvE\game\Game;

class AmpMonsterHealthArgIdentity extends ModifyMonsterArgIdentityBase {

	protected float $amp;

	public function __construct(float $amp) {
		parent::__construct();

		$this->amp = $amp;
	}

	public function getName(): string {
		return "モンスターの体力増強";
	}

	public function getDescription(): string {
		$plus = $this->amp - 1.0;
		$plus *= 100;
		return "モンスターの体力 + {$plus}%%";
	}

	public function getAmp(): float {
		return $this->amp;
	}

	protected function onSpawn(WaveMonsterSpawnEvent $event): void {
		foreach ($event->getOptions() as $id => $option) {
			$attribute = $option->getAttribute();
			$attribute->health = (int) ($attribute->health * $this->amp);
		}
	}
}
