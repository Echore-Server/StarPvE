<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\job\player\swordman;

use Lyrica0954\MagicParticle\ParticleOption;
use Lyrica0954\MagicParticle\SphereParticle;
use Lyrica0954\StarPvE\game\wave\DefaultMonsters;
use Lyrica0954\StarPvE\game\wave\MonsterData;
use Lyrica0954\StarPvE\job\Ability;
use Lyrica0954\StarPvE\job\AbilityStatus;
use Lyrica0954\StarPvE\job\ActionResult;
use Lyrica0954\StarPvE\job\Skill;
use Lyrica0954\StarPvE\job\ticking\Ticking;
use Lyrica0954\StarPvE\job\ticking\TickingController;
use Lyrica0954\StarPvE\translate\DescriptionTranslator;
use Lyrica0954\StarPvE\utils\EntityUtil;
use Lyrica0954\StarPvE\utils\ParticleUtil;
use Lyrica0954\StarPvE\utils\PlayerUtil;
use Lyrica0954\StarPvE\utils\VectorUtil;
use pocketmine\entity\Entity;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\network\mcpe\protocol\LevelEventPacket;
use pocketmine\network\mcpe\protocol\types\ActorEvent;
use pocketmine\network\mcpe\protocol\types\ParticleIds;
use pocketmine\world\particle\ExplodeParticle;

class ForceFieldSkill extends Skill {

    public function getCooltime(): int {
        return (20 * 20);
    }

    public function getName(): String {
        return "フォースフィールド";
    }

    public function getDescription(): String {
        $area = DescriptionTranslator::number($this->area, "m");
        $damage = DescriptionTranslator::health($this->damage);
        return
            sprintf('§b発動時:§f %1$s 以内の敵に %2$s のダメージを与えて、遠くに吹き飛ばす。', $area, $damage);
    }

    protected function init(): void {
        $this->damage = new AbilityStatus(6.0);
        $this->area = new AbilityStatus(9.0);
    }

    protected function onActivate(): ActionResult {
        $particle = new SphereParticle($this->area->get(), 8.5, 8.5);
        ParticleUtil::send($particle, $this->player->getWorld()->getPlayers(), $this->player->getPosition(), ParticleOption::spawnPacket("minecraft:basic_flame_particle", ""));

        PlayerUtil::broadcastSound($this->player->getPosition(), "block.false_permissions", 0.5);

        foreach (EntityUtil::getWithinRange($this->player->getPosition(), $this->area->get()) as $entity) {
            if (MonsterData::isMonster($entity)) {
                $xz = 6.0;
                $y = 2.0;
                if (MonsterData::equal($entity, DefaultMonsters::ATTACKER)) {
                    $xz = 2.0;
                    $y = 1.0;
                }

                $source = new EntityDamageByEntityEvent($this->player, $entity, EntityDamageByEntityEvent::CAUSE_ENTITY_ATTACK, $this->damage->get());

                EntityUtil::attackEntity($source, $xz, $y);
            }
        }

        return ActionResult::SUCCEEDED();
    }
}
