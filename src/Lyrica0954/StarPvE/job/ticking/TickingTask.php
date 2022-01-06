<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\task;

use Lyrica0954\StarPvE\job\player\PlayerJob;
use pocketmine\scheduler\Task;

use Lyrica0954\StarPvE\StarPvE;
use Lyrica0954\StarPvE\job\ticking\Ticking;
use pocketmine\player\Player;
use pocketmine\Server;

class TickingTask extends Task{

    public Ticking $ticking;
    protected $id;
    protected $count = 0;

    public static function addTicking(Ticking $ticking, int $period, string $id){
        $task = new self($ticking, $id);
        StarPvE::getInstance()->getScheduler()->scheduleRepeatingTask($task, $period);
        return $task;
    }

    public function __construct(Ticking $ticking, string $id) {
        $this->ticking = $ticking;
        $this->id = $id;
    }

    public function onRun() :void{
        $currentTick = Server::getInstance()->getTick();
        $this->count ++;
        $this->job->onTick($this->id, $this->count);
        if ($this->count >= 300){
            StarPvE::getInstance()->log("§7[JobTicking] §cWarning: sending to {$this->job->getName()} {$this->count} ticks");
        }
    }
}