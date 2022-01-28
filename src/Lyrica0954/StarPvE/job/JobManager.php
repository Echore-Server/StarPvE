<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\job;

use Lyrica0954\StarPvE\job\player\PlayerJob;
use Lyrica0954\StarPvE\StarPvE;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerItemUseEvent;
use pocketmine\item\ItemIds;
use pocketmine\player\Player;

class JobManager {

    private array $jobs;
    private array $players;

    public function __construct(){
        $this->players = [];
        $this->jobs = [];
    }

    public function register(PlayerJob $job){
        $this->jobs[] = $job::class;
    }

    public function getRegisteredJobs(){
        return $this->jobs;
    }

    public function setJob(Player $player, ?PlayerJob $job){
        $currentJob = $this->players[spl_object_hash($player)] ?? null;
        $currentJob?->close();
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

    public function isManaged(Player $player){
        return isset($this->players[spl_object_hash($player)]);
    }

    public function onItemUse(PlayerItemUseEvent $event){
        $item = $event->getItem();
        $player = $event->getPlayer();
        if ($this->isManaged($player)){
            $job = $this->getJob($player);
            $job->onItemUse($item);
        }
    }
}