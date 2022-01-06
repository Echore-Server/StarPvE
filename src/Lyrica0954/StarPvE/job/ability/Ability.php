<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\job\ability;

use Lyrica0954\StarPvE\job\ActionResult;
use Lyrica0954\StarPvE\job\cooltime\CooltimeHandler;
use Lyrica0954\StarPvE\job\player\PlayerJob;
use pocketmine\network\mcpe\protocol\types\ActorEvent;

abstract class Ability{

    protected CooltimeHandler $cooltimeHandler;

    public function __construct(PlayerJob $job){
        $this->cooltimeHandler = new CooltimeHandler("アビリティ", CooltimeHandler::BASE_TICK, 1);
    }

    public function getCooltimeHandler(): CooltimeHandler{
        return $this->cooltimeHandler;
    }

    abstract public function getCooltime(): int;

    public function activate(): ActionResult{
        if (!$this->cooltimeHandler->isActive()){
            $this->cooltimeHandler->start($this->getCooltime());

            return $this->onActivate();
        } else {
            return new ActionResult(ActionResult::FAILED_BY_COOLTIME);
        }
    }

    abstract public function onActivate(): ActionResult;
}