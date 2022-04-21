<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\data\player;

use Lyrica0954\StarPvE\data\adapter\SimpleConfigAdapter;
use Lyrica0954\StarPvE\data\player\adapter\GenericConfigAdapter;
use Lyrica0954\StarPvE\data\player\adapter\JobConfigAdapter;
use pocketmine\block\Planks;
use pocketmine\player\Player;
use pocketmine\utils\Config;

class PlayerConfig {

    private GenericConfigAdapter $generic;
    private SimpleConfigAdapter $job;

    public function __construct(Config $generic, Config $job, string $xuid){
        $this->generic = new GenericConfigAdapter($xuid, $generic);
        $this->job = new JobConfigAdapter($xuid, $job);
    }

    public function getGeneric(): GenericConfigAdapter{
        return $this->generic;
    }

    public function getJob(): JobConfigAdapter{
        return $this->job;
    }
}