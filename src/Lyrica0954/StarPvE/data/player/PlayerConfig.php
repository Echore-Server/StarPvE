<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\data\player;

use pocketmine\utils\Config;

class PlayerConfig {

    private Config $generic;
    private Config $job;

    public static function getExpToCompleteLevel(int $level){
        $exp = pow($level, 2) * 4 + 10;

        return $exp;
    }

    public static function getJobExpToCompleteLevel(int $level){
        $jobExp = pow($level, 3) * 4 + ($level * 20);

        return $jobExp;
    }

    public function __construct(Config $generic, Config $job){
        $this->generic = $generic;
        $this->job = $job;
    }

    public function getGeneric(): Config{
        return $this->generic;
    }

    public function getJob(): Config{
        return $this->job;
    }
}