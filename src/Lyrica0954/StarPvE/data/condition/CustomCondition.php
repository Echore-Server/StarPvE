<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\data\condition;

use pocketmine\player\Player;

class CustomCondition implements Condition{

    private \Closure $closure;

    public function __construct(\Closure $closure){
        $this->closure = $closure;
    }

    public function getClosure(): \Closure{
        return $this->closure;
    }

    public function check(Player $player): bool{
        return (($this->closure)($player));
    }

    public function asText(): string{
        return "";
    }
}