<?php

declare(strict_types=1);

namespace Lyrica0954\MagicParticle\effect;

use Lyrica0954\MagicParticle\CoveredParticle;
use Lyrica0954\MagicParticle\LineParticle;
use Lyrica0954\MagicParticle\SendableParticle;
use Lyrica0954\StarPvE\utils\RandomUtil;
use Lyrica0954\StarPvE\utils\VectorUtil;
use pocketmine\world\Position;

class SaturatedLineworkEffect extends ParticleEffect {

	public function __construct(
		private float $lineLength,
		private float $linePpb,
		private float $centerOffset, # 中心幅
		private int $amount,
		private float $maxYaw = 360,
		private float $minPitch = -90,
		private float $maxPitch = 90
	) {
	}

	public function draw(Position $pos): array {
		$particles = [];
		for ($i = 0; $i < $this->amount; $i++) {
			$yaw = lcg_value() * $this->maxYaw;
			$pitch = RandomUtil::rand_float($this->minPitch, $this->maxPitch);

			$dir = VectorUtil::getDirectionVector($yaw, $pitch);
			$start = $pos->addVector($dir->multiply($this->centerOffset));
			$lineDir = $dir->multiply($this->lineLength);
			$end = $pos->addVector($lineDir);
			$line = new LineParticle(VectorUtil::insertWorld($start, $pos->getWorld()), $this->linePpb);
			$particles[] = new CoveredParticle($line, VectorUtil::insertWorld($end, $pos->getWorld()));
		}
		return $particles;
	}
}
