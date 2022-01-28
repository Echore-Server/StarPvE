<?php

declare(strict_types=1);

namespace Lyrica0954\MagicParticle;

use Generator;
use pocketmine\world\Position;

class CircleParticle extends SendableParticle {

    public function __construct(private float $size, private float $space, private float $pitch, private float $maxYaw = 360){
        #これきもちー！！
    }

    public function draw(Position $pos): array{
        $pitchDe = ($this->pitch / 90);
        #$xz = cos(deg2rad($this->yaw));
        #$deXs = abs((abs($xz) - 1));
        $deX = (abs($pitchDe) - 1);
        #$deZ = 1 - $deXs;
        $positions = [];
        for($yaw = 0; $yaw < $this->maxYaw; $yaw += $this->space){
            $altYaw = $yaw - 180;
            $x = sin(deg2rad($yaw)) * -$deX;
            $z = cos(deg2rad($yaw)) * 1; #絶対固定

            $y = -sin(deg2rad($altYaw)) * $pitchDe;
            $positions[] = $pos->add($x * $this->size, $y * $this->size, $z * $this->size);
        }

        return $positions;
    }
}