<?php

declare(strict_types=1);

namespace Lyrica0954\MagicParticle;

use Generator;
use pocketmine\world\Position;

class LineParticle extends SendableParticle {

	public function __construct(private Position $start, private float $ppb) { #particles per block
		if ($this->ppb <= 0) {
			throw new \Exception("ppb must > 0");
		}
	}

	public function draw(Position $pos): array {
		$end = $pos;
		$start = $this->start;

		$subt = $end->subtractVector($start);

		$div = ($subt->length() * $this->ppb);
		$positions = [];
		if ($div > 0.0) {
			$step = $subt->divide($div);

			for ($i = 0; $i <= $div; $i++) {
				$positions[] = $start->addVector($step->multiply($i));
			}
		}

		return $positions;
	}
}
