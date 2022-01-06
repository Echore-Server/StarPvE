<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\job\ticking;

use Lyrica0954\StarPvE\task\TickingTask;

trait TickingController {

    protected $task = [];

    public function startTicking(String $id, int $period){
        $task = TickingTask::addTicking($this, $period, $id);
        $this->task[$id] = $task;
        return $task;
    }

    public function stopTicking(String $id){
        if (isset($this->task[$id])){
            if ($this->task[$id] instanceof TickingTask){
                $this->task[$id]->getHandler()->cancel();
            } else {
                throw new \Exception("JobTicking id {$id} is not instance of JobTicking");
            }
        } else {
            throw new \Exception("JobTicking id {$id} not found");
        }
    }
}