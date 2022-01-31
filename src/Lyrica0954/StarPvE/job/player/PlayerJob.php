<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\job\player;

use Lyrica0954\StarPvE\job\Ability;
use Lyrica0954\StarPvE\job\Skill;
use Lyrica0954\StarPvE\job\cooltime\CooltimeAttachable;
use Lyrica0954\StarPvE\job\cooltime\CooltimeHandler;
use Lyrica0954\StarPvE\job\cooltime\CooltimeNotifier;
use Lyrica0954\StarPvE\job\IdentityGroup;
use Lyrica0954\StarPvE\job\Job;
use Lyrica0954\StarPvE\StarPvE;
use pocketmine\entity\Skin;
use pocketmine\event\Listener;
use pocketmine\item\Item;
use pocketmine\item\ItemIds;
use pocketmine\player\Player;
use pocketmine\Server;

abstract class PlayerJob extends Job{

    protected ?Player $player;

    protected Ability $ability;
    protected Skill $skill;

    protected IdentityGroup $identityGroup;

    protected CooltimeNotifier $cooltimeNotifier;

    public function __construct(?Player $player){
        if ($player instanceof Player){ #JobManager への登録を簡単にするため
            $this->player = $player;

            $this->log("§dCreated for {$player->getName()}");

            $this->ability = $this->getInitialAbility();
            $this->skill = $this->getInitialSkill();
            $this->identityGroup = $this->getInitialIdentityGroup();
            $this->identityGroup->apply($this);
    
            $this->cooltimeNotifier = new CooltimeNotifier($player);
            $this->cooltimeNotifier->addCooltimeHandler($this->ability->getCooltimeHandler());
            $this->cooltimeNotifier->addCooltimeHandler($this->skill->getCooltimeHandler());
            $this->cooltimeNotifier->start();

            if ($this instanceof Listener) Server::getInstance()->getPluginManager()->registerEvents($this, StarPvE::getInstance());
        } else {
            $this->player = null;

            #$this->log("§dCreated for none");

            $this->ability = $this->getInitialAbility();
            $this->skill = $this->getInitialSkill();
        }
    }

    public function close(){
        $this->ability->close();
        $this->skill->close();
        $this->cooltimeNotifier->stop();
        $this->identityGroup->reset($this);
        $this->identityGroup->close();

        $this->player = null;
        $this->log("§dClosed");
    }

    public function onItemUse(Item $item){
        if ($item->getId() === ItemIds::BOOK){
            $activated = null;
            if ($this->player->isSneaking()){
                $result = $this->skill->activate();
                $activated = $this->skill;
            } else {
                $result = $this->ability->activate();
                $activated = $this->ability;
            }

            $name = $activated->getCooltimeHandler()->getId();
            $this->log("Activated {$name}");
            if ($result->isFailedByCooltime()){
                $this->player->sendMessage("§c現在{$name}はクールタイム中です！");
            } elseif($result->isFailedAlreadyActive()){
                $this->player->sendMessage("§c{$name}は既にアクティブです！");
            } elseif ($result->isSucceeded()){
                $this->player->sendMessage("§a{$name}を発動しました！");
            } elseif ($result->isFailed()){
                $this->player->sendMessage("§c{$name}を発動できません！");
            } elseif ($result->isAbandoned()){
                #bomb!
            }
        }
    }

    public function getPlayer(): ?Player{
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

    abstract protected function getInitialIdentityGroup(): IdentityGroup;

    public function canActivateAbility(): bool{
        return !$this->ability->getCooltimeHandler()->isActive();
    }

    public function canActivateSkill(): bool{
        return !$this->skill->getCooltimeHandler()->isActive();
    }

    public function log(string $message){
        StarPvE::getInstance()->log("§7[PlayerJob - {$this->getName()}] {$message}");
    }

}