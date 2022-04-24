<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\job;

use Lyrica0954\StarPvE\data\player\PlayerConfig;
use Lyrica0954\StarPvE\data\player\PlayerDataCenter;
use Lyrica0954\StarPvE\event\job\player\PlayerLeftJobEvent;
use Lyrica0954\StarPvE\event\job\player\PlayerSelectJobEvent;
use Lyrica0954\StarPvE\job\player\PlayerJob;
use Lyrica0954\StarPvE\StarPvE;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerItemUseEvent;
use pocketmine\item\ItemIds;
use pocketmine\player\Player;

class JobManager {

    /**
     * @var string[]
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
        $name = (new \ReflectionClass($job))->getShortName();
        $this->jobs[$name] = $job::class;
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

    public function get(string $name): ?string{
        return $this->jobs[$name];
    }

    public function setJob(Player $player, ?string $job){
        $currentJob = $this->players[spl_object_hash($player)] ?? null;
        if ($currentJob !== null){
            $currentJob->close();
            $lev = new PlayerLeftJobEvent($player, $currentJob);
            $lev->call();
        }
        if ($job !== null){
            $ref = new \ReflectionClass($job);
            $name = $ref->getShortName();
            $playerConfig = PlayerDataCenter::getInstance()->get($player);
            if ($playerConfig instanceof PlayerConfig){
                if ($playerConfig->getJob($name) == null){
                    $jobConfig = PlayerDataCenter::getInstance()->createJobConfig($player, $name);
                    $playerConfig->addJob($name, $jobConfig);
                }
            }
            $jobInstance = new $job($player);
            $ev = new PlayerSelectJobEvent($player, $jobInstance);
            $ev->call();
            $this->players[spl_object_hash($player)] = $jobInstance;
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
        return (($job = $this->getJob($player)) !== null ? $job->getName() : "None");
    }

    public function isJobName(Player $player, string $jobName){
        return ($this->getJobName($player) === $jobName);
    }

    public function isManaged(Player $player){
        return isset($this->players[spl_object_hash($player)]);
    }

    public function onItemUse(PlayerItemUseEvent|PlayerInteractEvent $event){
        $item = $event->getItem();
        $player = $event->getPlayer();
        if ($this->isManaged($player)){
            $job = $this->getJob($player);
            $job->onItemUse($item);
        }
    }
}