<?php

declare(strict_types=1);

namespace Lyrica0954\MagicParticle;

use Generator;
use pocketmine\world\Position;

class LineParticle extends SendableParticle {

    public function __construct(private Position $start, private float $ppb){ #particles per block
    }

    public function draw(Position $pos): array{
        $end = $pos;
        $start = $this->start;

        $subt = $end->subtractVector($start);
        
        $div = 1 + ($subt->length() * $this->ppb);
        $step = $subt->divide($div);
        $positions = [];
        for ($i = 0; $i <= $div; $i ++){
            $positions[] = $start->addVector($step->multiply($i));
        }

        return $positions;
    }
}