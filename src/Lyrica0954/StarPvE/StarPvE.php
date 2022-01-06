<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE;

use Lyrica0954\StarPvE\job\JobManager;
use pocketmine\plugin\PluginBase;

final class StarPvE extends PluginBase {

    private static ?StarPvE $instance = null;

    public static function getInstance(): ?StarPvE{
        return self::$instance;
    }

    private JobManager $jobManager;

    public function getJobManager(): JobManager{
        return $this->jobManager;
    }

    protected function onLoad(): void{
        self::$instance = $this;

        $this->jobManager = new JobManager();
    }

    public function log(string $message){
        $this->getServer()->getLogger()->info("§c[StarPvE] §f{$message}");
    }
}