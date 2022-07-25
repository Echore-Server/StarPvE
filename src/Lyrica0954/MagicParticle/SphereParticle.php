<?php

declare(strict_types=1);

namespace Lyrica0954\MagicParticle;

use Generator;
use Lyrica0954\SmartEntity\utils\VectorUtil;
use pocketmine\world\Position;

class SphereParticle extends SendableParticle {

    public function __construct(private float $size, private float $yawSpace, private float $pitchSpace, private float $maxYaw = 360, private float $minPitch = -90, private float $maxPitch = 90) {
    }

    public function draw(Position $pos): array {
        $positions = [];
        for ($yaw = 0; $yaw < $this->maxYaw; $yaw += $this->yawSpace) {
            for ($pitch = $this->minPitch; $pitch < $this->maxPitch; $pitch += $this->pitchSpace) {
                $dir = VectorUtil::getDirectionVector($yaw, $pitch);
                $positions[] = $pos->addVector($dir->multiply($this->size));
            }
        }

        return $positions;
    }
}
