<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\job\player;

use Lyrica0954\StarPvE\job\Ability;
use Lyrica0954\StarPvE\job\Skill;
use Lyrica0954\StarPvE\job\cooltime\CooltimeAttachable;
use Lyrica0954\StarPvE\job\cooltime\CooltimeHandler;
use Lyrica0954\StarPvE\job\cooltime\CooltimeNotifier;
use Lyrica0954\StarPvE\job\Job;
use pocketmine\entity\Skin;
use pocketmine\player\Player;

abstract class PlayerJob extends Job implements CooltimeAttachable{

    protected Player $player;

    protected Ability $ability;
    protected Skill $skill;

    protected CooltimeNotifier $cooltimeNotifier;

    public function __construct(?Player $player){
        if ($player instanceof Player){ #JobManager への登録を簡単にするため
            $this->player = $player;

            $this->ability = $this->getInitialAbility();
            #$this->ability->getCooltimeHandler()->attach($this);
            $this->skill = $this->getInitialSkill();
            #$this->skill->getCooltimeHandler()->attach($this); #todo: 
    
            $this->cooltimeNotifier = new CooltimeNotifier($player);
            $this->cooltimeNotifier->addCooltimeHandler($this->ability->getCooltimeHandler());
            $this->cooltimeNotifier->addCooltimeHandler($this->skill->getCooltimeHandler());
            $this->cooltimeNotifier->start();
        }
    }

    public function getPlayer(): Player{
        return $this->player;
    }

    public function getCooltimeNotifier(): CooltimeNotifier{
        return $this->cooltimeNotifier;
    }

    public function getAbility(): Ability{
        return $this->ability;
    }

    public function getSkill(): Skill{
        return $this->skill;
    }

    abstract protected function getInitialAbility(): Ability;

    abstract protected function getInitialSkill(): Skill;

    public function canActivateAbility(): bool{
        return !$this->ability->getCooltimeHandler()->isActive();
    }

    public function canActivateSkill(): bool{
        return !$this->skill->getCooltimeHandler()->isActive();
    }

}