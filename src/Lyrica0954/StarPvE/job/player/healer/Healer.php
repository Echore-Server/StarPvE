<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\job\player\healer;

use Lyrica0954\MagicParticle\CircleParticle;
use Lyrica0954\StarPvE\data\condition\Condition;
use Lyrica0954\StarPvE\identity\Identity;
use Lyrica0954\StarPvE\identity\IdentityGroup;
use Lyrica0954\StarPvE\identity\player\AddMaxHealthIdentity;
use Lyrica0954\StarPvE\job\Ability;
use Lyrica0954\StarPvE\job\AlwaysAbility;
use Lyrica0954\StarPvE\job\identity\ability\AttachAbilityIdentityBase;
use Lyrica0954\StarPvE\job\identity\ability\IncreaseAreaIdentity;
use Lyrica0954\StarPvE\job\player\healer\identity\FastFeedIdentity;
use Lyrica0954\StarPvE\job\player\PlayerJob;
use Lyrica0954\StarPvE\job\Skill;
use Lyrica0954\StarPvE\StarPvE;
use Lyrica0954\StarPvE\utils\EntityUtil;
use Lyrica0954\StarPvE\utils\VectorUtil;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\Listener;
use pocketmine\player\Player;
use pocketmine\Server;

class Healer extends PlayerJob implements AlwaysAbility, Listener {

    protected function getInitialIdentityGroup(): IdentityGroup {
        $p = $this->player instanceof Player;
        $g = new IdentityGroup();
        $list = [
            $p ? new AddMaxHealthIdentity($this->player, null, 2) : null,
            new IncreaseAreaIdentity($this, null, AttachAbilityIdentityBase::ATTACH_ABILITY, 0.5),
            $p ? new FastFeedIdentity($this->player, null, 30) : null
        ];
        $g->addAllSafe($list);
        return $g;
    }

    protected function getInitialAbility(): Ability {
        return new HarmonyAbility($this);
    }

    protected function getInitialSkill(): Skill {
        return new ConcordeSkill($this);
    }

    public function getName(): string {
        return "Healer";
    }

    public function getDescription(): string {
        return
            "§7- §l§a支援[♡]§r

味方を回復できるヒーラー。
味方を支援したり、回復させたりすることができる。";
    }

    public function getAlAbilityName(): string {
        return "ハートウォーミング";
    }

    public function getAlAbilityDescription(): string {
        return "自分から半径 §c7m§f 以内にいる味方が攻撃を受けた場合、その攻撃のダメージを §c30%%§f 軽減させる。";
    }

    public function getSelectableCondition(): ?Condition {
        return null;
    }

    public function onEntityDamage(EntityDamageEvent $event) {
        $entity = $event->getEntity();
        if ($entity instanceof Player) {
            if ($this->player instanceof Player) {
                if ($entity !== $this->player) {
                    $gp = StarPvE::getInstance()->getGamePlayerManager();
                    if ($gp->areSameGame($entity, $this->player)) {
                        $dist = $entity->getPosition()->distance($this->player->getPosition());
                        if ($dist <= 7) {
                            EntityUtil::multiplyFinalDamage($event, 0.7);
                        }
                    }
                }
            }
        }
    }
}
