<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\job\cooltime;

use Lyrica0954\StarPvE\StarPvE;
use pocketmine\player\Player;
use pocketmine\scheduler\Task;

class CooltimeNotifier{

    protected array $cooltimes;
    protected Player $player;

    private ?Task $task;

    public function __construct(Player $player){
        $this->player = $player;
        $this->cooltimes = [];
        $this->task = null;
        
    }

    public function log(string $message){
        StarPvE::getInstance()->log("§7[CooltimeNotifier] {$message}");
    }

    public function start(){
        $this->log("Started the Task");

        $this->task = new class($this) extends Task{

            private CooltimeNotifier $cooltimeNotifier;

            public function __construct(CooltimeNotifier $cooltimeNotifier){
                $this->cooltimeNotifier = $cooltimeNotifier;
            }

            public function onRun(): void{
                $this->cooltimeNotifier->tick();
            }

        };
        StarPvE::getInstance()->getScheduler()->scheduleRepeatingTask($this->task, 20); #todo: to class method
    }

    public function stop(){
        if ($this->task instanceof Task){
            $this->task->getHandler()->cancel();
            $this->log("Stopped the Task");
        }
    }

    public function tick(){
        $text = "";
        foreach($this->cooltimes as $cooltimeHandler){
            $seconds = round($cooltimeHandler->getRemain() / 20);
            $status = (!$cooltimeHandler->isActive() ? "§a使用可能": "§c残り {$seconds}秒");
            $text .= "§7{$cooltimeHandler->getId()}: {$status}\n";
        }

        $this->player->sendPopup($text);
    }

    public function addCooltimeHandler(CooltimeHandler $cooltimeHandler){
        $this->cooltimes[] = $cooltimeHandler;
        $this->log("Added cooltime handler: {$cooltimeHandler->getId()}");
    }
}