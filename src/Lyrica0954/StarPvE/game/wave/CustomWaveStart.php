<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\game\wave;

use Lyrica0954\StarPvE\game\Game;

class CustomWaveStart {

    private ?\Closure $closure;

    public function __construct(?\Closure $closure = null){
        $this->closure = $closure;
    }

    public function getClosure(): ?\Closure{
        return $this->closure;
    }
}