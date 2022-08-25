<?php

declare(strict_types=1);

namespace Lyrica0954\MagicParticle\effect;

use Lyrica0954\MagicParticle\CoveredParticle;
use Lyrica0954\MagicParticle\LineParticle;
use Lyrica0954\MagicParticle\utils\RandomUtil;
use pocketmine\world\Position;

class LightningEffect extends ParticleEffect {

	public function __construct(
		protected Position $start,
		protected float $impact,
		protected float $impactPerBlock,
		protected int $lppb = 3
	) {
	}

	public function draw(Position $pos): array {
		$subt = $pos->subtractVector($this->start);
		$div = ($subt->length() * $this->impactPerBlock);

		$particles = [];
		if ($div > 0.0) {
			$step = $subt->divide($div);

			$last = $this->start;

			for ($i = 0; $i <= $div; $i++) {
				$target = $this->start->addVector($step->multiply($i));
				if ($i !== $div) {
					$target = $target->addVector(RandomUtil::vector3(-$this->impact, $this->impact));
				}

				$particle = new LineParticle(Position::fromObject($last, $pos->getWorld()), $this->lppb);
				$particles[] = new CoveredParticle($particle, Position::fromObject($target, $pos->getWorld()));
				$last = clone $target;
			}
		}

		return $particles;
	}
}
