<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\job;

use Lyrica0954\StarPvE\job\ActionResult;
use Lyrica0954\StarPvE\job\cooltime\CooltimeHandler;
use Lyrica0954\StarPvE\job\player\PlayerJob;
use pocketmine\network\mcpe\protocol\types\ActorEvent;
use pocketmine\player\Player;

abstract class Ability{

    protected bool $active;

    protected bool $closed;

    protected PlayerJob $job;
    protected ?Player $player;
    protected CooltimeHandler $cooltimeHandler;

    public function __construct(PlayerJob $job){
        $this->job = $job;
        $this->player = $job->getPlayer();
        $this->closed = false;
        $this->active = false;
        $this->cooltimeHandler = new CooltimeHandler("アビリティ", CooltimeHandler::BASE_TICK, 1);
    }

    public function close(){
        $this->cooltimeHandler->forceStop();
    }

    public function isActive(): bool{
        return $this->active;
    }

    public function getCooltimeHandler(): CooltimeHandler{
        return $this->cooltimeHandler;
    }

    public function getPlayer(): Player{
        return $this->player;
    }

    public function getJob(): PlayerJob{
        return $this->job;
    }

    abstract public function getCooltime(): int;

    public function activate(): ActionResult{
        if (!$this->closed){
            if (!$this->cooltimeHandler->isActive()){
                if (!$this->active){
                    $this->cooltimeHandler->start($this->getCooltime());
    
                    return $this->onActivate();
                } else {
                    return ActionResult::FAILED_ALREADY_ACTIVE();
                }
            } else {
                return ActionResult::FAILED_BY_COOLTIME();
            }   
        } else {
            throw new \Exception("cannot activate closed ability");
        }
    }

    abstract protected function onActivate(): ActionResult;
}