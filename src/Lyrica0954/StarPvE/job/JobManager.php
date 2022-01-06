<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\job;

use Lyrica0954\StarPvE\job\player\PlayerJob;
use Lyrica0954\StarPvE\StarPvE;
use pocketmine\player\Player;

class JobManager {

    private array $players;

    public function __construct(){
        $this->players = [];
    }

    public function setJob(Player $player, PlayerJob $job){
        $this->players[spl_object_hash($player)] = $job;
    }
    
    public function getJob(Player $player): ?PlayerJob{
        return $this->players[spl_object_hash($player)] ?? null;
    }

    public function equalJob(Player $a, Player $b){
        return ($this->getJobName($a)) === ($this->getJobName($b));
    }

    public function getJobName(Player $player){
        return $this->getJob($player)->getName();
    }

    public function isJobName(Player $player, string $jobName){
        return ($this->getJobName($player) === $jobName);
    }
}