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
    /**
     * @var JobConfigAdapter[]
     */
    private array $jobs;

    private string $xuid;

    /**
     * @param Config $generic
     * @param Config[] $jobs
     * @param string $xuid
     */
    public function __construct(Config $generic, array $jobs, string $xuid){
        $this->generic = new GenericConfigAdapter($xuid, $generic);
        $this->jobs = [];
        $this->xuid = $xuid;
        foreach($jobs as $name=>$jobConfig){
            $this->jobs[$name] = new JobConfigAdapter($xuid, $jobConfig);
        }
    }

    public function getGeneric(): GenericConfigAdapter{
        return $this->generic;
    }

    /**
     * @return JobConfigAdapter[]
     */
    public function getJobs(): array{
        return $this->jobs;
    }

    public function getJob(string $name): ?JobConfigAdapter{
        return $this->jobs[$name] ?? null;
    }

    public function addJob(string $name, Config $job): void{
        $this->jobs[$name] = new JobConfigAdapter($this->xuid, $job);
    }
}