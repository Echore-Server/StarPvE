<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\job\player\swordman;

use Lyrica0954\StarPvE\data\condition\Condition;
use Lyrica0954\StarPvE\game\wave\MonsterData;
use Lyrica0954\StarPvE\identity\Identity;
use Lyrica0954\StarPvE\identity\IdentityGroup;
use Lyrica0954\StarPvE\job\Ability;
use Lyrica0954\StarPvE\job\AlwaysAbility;
use Lyrica0954\StarPvE\job\identity\ability\AddBaseAreaIdentity;
use Lyrica0954\StarPvE\job\identity\ability\AddBaseDamageIdentity;
use Lyrica0954\StarPvE\job\identity\ability\AttachAbilityIdentityBase;
use Lyrica0954\StarPvE\job\identity\ability\IncreaseDamageIdentity;
use Lyrica0954\StarPvE\job\JobIdentityGroup;
use Lyrica0954\StarPvE\job\player\PlayerJob;
use Lyrica0954\StarPvE\job\Skill;
use Lyrica0954\StarPvE\translate\DescriptionTranslator;
use Lyrica0954\StarPvE\utils\EntityUtil;
use Lyrica0954\StarPvE\utils\PlayerUtil;
use ParentIterator;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\entity\Entity;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\Listener;
use pocketmine\network\mcpe\protocol\types\ParticleIds;
use pocketmine\player\Player;

class Swordman extends PlayerJob implements AlwaysAbility, Listener {

    protected function getInitialAbility(): Ability {
        return new LeapAbility($this);
    }

    protected function getInitialSkill(): Skill {
        return new ForceFieldSkill($this);
    }

    protected function getInitialIdentityGroup(): IdentityGroup {
        $g = new IdentityGroup();
        $list = [
            new IncreaseDamageIdentity($this, null, AttachAbilityIdentityBase::ATTACH_SKILL, 0.5)
        ];
        $g->addAll($list);
        return $g;
    }

    public function getName(): string {
        return "Swordman";
    }

    public function getDescription(): string {
        return
            "§7- §l§c戦闘§r

俊敏に動けるソードマン。移動や、敵の吹き飛ばしなど、先陣を突っ切っていくのが得意な職業。
この職業はどの能力もクールタイムが短いため、どんどん使っていこう。";
    }

    public function getAlAbilityName(): string {
        return "シールド";
    }

    public function getAlAbilityDescription(): string {
        return
            "自分が受けるダメージを (§c6m§f 以内にいる敵の数 x §c3§f)%% 軽減する(最大§c12体§f分)
もし受けるダメージが自身の体力の半分以上の場合自身に回復効果を付与する";
    }

    public function getSelectableCondition(): ?Condition {
        return null;
    }

    public function onEntityDamage(EntityDamageEvent $event) {
        $entity = $event->getEntity();
        if ($entity === $this->player) {
            if ($entity instanceof Player) {

                $entities = array_filter(
                    EntityUtil::getWithinRange($entity->getPosition(), 6.0),
                    function (Entity $entity) {
                        return (MonsterData::isMonster($entity));
                    }
                );
                $count = min(12, count($entities));

                $reduce = ($count) * 0.03;

                EntityUtil::multiplyFinalDamage($event, (1.0 - $reduce));

                if ($event->getFinalDamage() >= ($entity->getMaxHealth() / 2)) {
                    $entity->getEffects()->add(new EffectInstance(VanillaEffects::REGENERATION(), (8 * 20), 3));
                    PlayerUtil::playSound($entity, "random.totem", 1.0, 0.6);
                }
            }
        }
    }
}
