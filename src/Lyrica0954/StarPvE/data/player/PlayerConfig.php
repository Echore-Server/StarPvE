<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\data\player;

use Lyrica0954\StarPvE\data\adapter\SimpleConfigAdapter;
use Lyrica0954\StarPvE\data\player\adapter\GenericConfigAdapter;
use pocketmine\block\Planks;
use pocketmine\player\Player;
use pocketmine\utils\Config;

class PlayerConfig {

    private GenericConfigAdapter $generic;
    private SimpleConfigAdapter $job;

    public static function getExpToCompleteLevel(int $level){
        $exp = pow($level, 2) * 4 + 10;

        return $exp;
    }

    public static function getJobExpToCompleteLevel(int $level){
        $jobExp = pow($level, 3) * 4 + ($level * 20);

        return $jobExp;
    }

    public function __construct(Config $generic, Config $job, string $xuid){
        $this->generic = new GenericConfigAdapter($xuid, $generic);
        $this->job = new SimpleConfigAdapter($job);
    }

    public function getGeneric(): GenericConfigAdapter{
        return $this->generic;
    }

    public function getJob(): SimpleConfigAdapter{
        return $this->job;
    }
}