<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\data\condition;

use Lyrica0954\StarPvE\data\player\adapter\GenericConfigAdapter;
use Lyrica0954\StarPvE\data\player\adapter\JobConfigAdapter;
use Lyrica0954\StarPvE\data\player\PlayerDataCenter;
use Lyrica0954\StarPvE\data\player\PlayerDataCollector;
use pocketmine\player\Player;

class JobLevelCondition implements Condition {

    public int $min;

    public string $jobName;

    public function __construct(int $min, string $jobName) {
        $this->min = $min;
        $this->jobName = $jobName;
    }

    public function check(Player $player): bool {
        $adapter = JobConfigAdapter::fetch($player, $this->jobName);
        $level = $adapter?->getConfig()->get(JobConfigAdapter::LEVEL, null) ?? 0;
        return $level >= $this->min;
    }

    public function asText(): string {
        return "{$this->jobName} の職業レベル {$this->min} 以上";
    }
}
