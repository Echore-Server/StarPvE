<?php

declare(strict_types=1);

namespace Lyrica0954\MagicParticle;

use Generator;
use pocketmine\world\Position;

class SingleParticle extends SendableParticle {

    public function __construct(){
        #これきもちー！！
    }

    public function draw(Position $pos): array{
        return [$pos];
    }
}