<?php

declare(strict_types=1);

namespace Lyrica0954\MagicParticle\effect;

use Lyrica0954\MagicParticle\PartDelayedParticle;
use pocketmine\world\Position;

class PartDelayedEffect extends ParticleEffect {

	private array $drawHooks;

	public function __construct(
		private ParticleEffect $effect,
		private int $period,
		private int $partLength = 1,
		private bool $reverse = false
	) {
		$this->drawHooks = [];
	}

	public function addDrawHook(\Closure $closure) {
		$this->drawHooks[] = $closure;
	}

	public function draw(Position $pos): array {
		$effectParticles = $this->effect->draw($pos);
		$delayedParticles = [];
		foreach ($effectParticles as $particle) {
			$delayed =  new PartDelayedParticle($particle, $this->period, $this->partLength, $this->reverse);
			foreach ($this->drawHooks as $hook) {
				$delayed->addDrawHook($hook);
			}

			$delayedParticles[] = $delayed;
		}

		return $delayedParticles;
	}
}
