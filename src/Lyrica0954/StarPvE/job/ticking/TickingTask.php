<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\job\ticking;

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
        $this->count ++;
        $this->ticking->onTick($this->id, $this->count);
        if ($this->count >= 300){
            $name = (new \ReflectionClass($this->ticking))->getShortName();
            StarPvE::getInstance()->log("§7[TickingTask] §cWarning: sending to \"{$name}\"(id: {$this->id}) {$this->count} ticks");
        }
    }
}