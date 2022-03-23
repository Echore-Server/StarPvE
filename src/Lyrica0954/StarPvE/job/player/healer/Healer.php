<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\job\player\healer;

use Lyrica0954\StarPvE\data\condition\Condition;
use Lyrica0954\StarPvE\job\Ability;
use Lyrica0954\StarPvE\job\AlwaysAbility;
use Lyrica0954\StarPvE\job\Identity;
use Lyrica0954\StarPvE\job\identity\ability\AddBaseAreaIdentity;
use Lyrica0954\StarPvE\job\identity\AddMaxHealthIdentity;
use Lyrica0954\StarPvE\job\IdentityGroup;
use Lyrica0954\StarPvE\job\player\healer\ident\HealerIdent1;
use Lyrica0954\StarPvE\job\player\healer\identity\FastFeedIdentity;
use Lyrica0954\StarPvE\job\player\PlayerJob;
use Lyrica0954\StarPvE\job\Skill;
use Lyrica0954\StarPvE\StarPvE;
use Lyrica0954\StarPvE\utils\EntityUtil;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\Listener;
use pocketmine\player\Player;

class Healer extends PlayerJob implements AlwaysAbility, Listener{

    protected function getInitialIdentityGroup(): IdentityGroup{
        $g = new IdentityGroup($this);
        $lists = [
            Identity::setCondition(new AddMaxHealthIdentity($this, 2), null),
            Identity::setCondition(new AddBaseAreaIdentity($this, AddBaseAreaIdentity::ATTACH_ABILITY, 0.5), null),
            Identity::setCondition(new FastFeedIdentity($this, 30), null)
        ];

        foreach($lists as $identity){
            $g->add($identity);
        }
        return $g;
    }

    protected function getInitialAbility(): Ability{
        return new HarmonyAbility($this);
    }

    protected function getInitialSkill(): Skill{
        return new ConcordeSkill($this);
    }

    public function getName(): string{
        return "Healer";
    }

    public function getDescription(): string{
        return 
"§7- §l§a支援[♡]§r

味方を回復できるヒーラー。";
    }

    public function getAlAbilityName(): string{
        return "ハートウォーミング";
    }

    public function getAlAbilityDescription(): string{
        return "自分から半径 §c6m§f 以内にいる味方が攻撃を受けた場合、その攻撃のダメージを §c10%%§f 軽減させる。";
    }

    public function getSelectableCondition(): ?Condition{
        return null;
    }

    public function onEntityDamage(EntityDamageEvent $event){
        $entity = $event->getEntity();
        if ($entity instanceof Player){
            if ($this->player instanceof Player){
                if ($entity !== $this->player){
                    $gp = StarPvE::getInstance()->getGamePlayerManager();
                    if ($gp->areSameGame($entity, $this->player)){
                        EntityUtil::multiplyFinalDamage($event, 0.9);
                    }
                }
            }
        }
    }
}