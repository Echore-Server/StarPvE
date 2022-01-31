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

    /**
     * @var string[] #class name
     */
    private array $jobs;
    /**
     * @var PlayerJob[]
     */
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

    public function getSelectableJobs(Player $player): array{
        $selectable = [];

        foreach($this->jobs as $class){
            $job = new $class(null);

            if ($job instanceof Job){
                if ($job->isSelectable($player)){
                    $selectable[] = $class;
                }
            }
        }

        return $selectable;
    }

    public function setJob(Player $player, ?string $job){
        $currentJob = $this->players[spl_object_hash($player)] ?? null;
        $currentJob?->close();
        if ($job !== null){
            $this->players[spl_object_hash($player)] = new $job($player);
        } else {
            $this->players[spl_object_hash($player)] = null;
        }
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