<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\job\player\swordman;

use Lyrica0954\StarPvE\job\Ability;
use Lyrica0954\StarPvE\job\ActionResult;
use Lyrica0954\StarPvE\job\ticking\Ticking;
use Lyrica0954\StarPvE\job\ticking\TickingController;
use Lyrica0954\StarPvE\utils\VectorUtil;
use pocketmine\network\mcpe\protocol\types\ActorEvent;

class LeapAbility extends Ability implements Ticking{
    use TickingController;

    public float $groundPower = 3.25;
    public float $airPower = 2.0;

    private array $damaged = [];

    public function getCooltime(): int{
        return 6 * 20;
    }

    protected function onActivate(): ActionResult{
        if ($this->player->isOnGround()){
            $dir = VectorUtil::getDirectionHorizontal($this->player->getLocation()->getYaw())->multiply($this->groundPower);
            $dir->y = 0.6;
        } else {
            $dir = VectorUtil::getDirectionHorizontal($this->player->getLocation()->getYaw())->multiply($this->airPower);
            $dir->y = 1.05;
        }
        $this->player->setMotion($dir);
        $this->startTicking("leapEffect", 1);
        return new ActionResult(ActionResult::SUCCEEDED);
    }

    public function onTick(string $id, int $tick): void{
        if ($id === "leapEffect"){
            if ($this->player->isOnGround() || $tick >= 45){
                $this->damaged = [];
                $this->stopTicking($id);
            } else {
                
            }
        }
    }
}