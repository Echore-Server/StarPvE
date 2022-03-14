<?php

declare(strict_types=1);

namespace Lyrica0954\MagicParticle;

use Generator;
use Lyrica0954\StarPvE\utils\RandomUtil;
use pocketmine\world\Position;

class CircleParticle extends SendableParticle {

    public function __construct(private float $size, private float $space, private float $pitch = 0, private float $maxYaw = 360, private float $unstableRate = 0){
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
            $size = $this->size;
            $size += RandomUtil::rand_float(-$this->unstableRate, $this->unstableRate);
            $positions[] = $pos->add($x * $size, $y * $size, $z * $size);
        }

        return $positions;
    }
}