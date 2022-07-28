<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\identity\player;

use Lyrica0954\StarPvE\data\condition\Condition;
use Lyrica0954\StarPvE\identity\Identity;
use Lyrica0954\StarPvE\job\JobIdentity;
use Lyrica0954\StarPvE\job\player\PlayerJob;
use Lyrica0954\StarPvE\utils\BuffUtil;
use Lyrica0954\StarPvE\utils\EntityUtil;
use pocketmine\player\Player;

class SpeedPercentageArgIdentity extends PlayerArgIdentity {

    protected float $percentage;

    public function __construct(?Condition $condition = null, float $percentage) {
        parent::__construct($condition);
        $this->percentage = $percentage;
    }

    public function getName(): string {
        $c = ($this->percentage <= 1) ? "減少" : "増加";
        return "移動速度{$c}";
    }

    public function getDescription(): string {
        $oper = "";
        if ($this->percentage <= 1) {
            $percentage = round((1.0 - $this->percentage) * 100);
            $oper = "-";
        } else {
            $percentage = round(($this->percentage - 1.0) * 100);
            $oper = "+";
        }
        return "移動速度 §c{$oper}{$percentage}%";
    }

    public function apply(): void {
        if ($this->player !== null) {
            $this->player->setMovementSpeed($this->player->getMovementSpeed() * $this->percentage);
        }
    }

    public function reset(): void {
        if ($this->player !== null) {
            $this->player->setMovementSpeed($this->player->getMovementSpeed() / $this->percentage);
        }
    }
}
