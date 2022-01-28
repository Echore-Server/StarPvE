<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\job\player\swordman;

use Lyrica0954\MagicParticle\SphereParticle;
use Lyrica0954\StarPvE\game\wave\MonsterData;
use Lyrica0954\StarPvE\job\Ability;
use Lyrica0954\StarPvE\job\ActionResult;
use Lyrica0954\StarPvE\job\Skill;
use Lyrica0954\StarPvE\job\ticking\Ticking;
use Lyrica0954\StarPvE\job\ticking\TickingController;
use Lyrica0954\StarPvE\utils\EntityUtil;
use Lyrica0954\StarPvE\utils\PlayerUtil;
use Lyrica0954\StarPvE\utils\VectorUtil;
use pocketmine\entity\Entity;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\network\mcpe\protocol\LevelEventPacket;
use pocketmine\network\mcpe\protocol\types\ActorEvent;
use pocketmine\network\mcpe\protocol\types\ParticleIds;
use pocketmine\world\particle\ExplodeParticle;

class ForceFieldSkill extends Skill{

    public function getCooltime(): int{
        return (25 * 20);
    }

    protected function onActivate(): ActionResult{
        $particle = new SphereParticle(9.0, 8.5);
        $particle->sendToPlayers($this->player->getWorld()->getPlayers(), $this->player->getPosition(), "minecraft:basic_flame_particle");

        PlayerUtil::broadcastSound($this->player->getPosition(), "block.false_permissions", 0.5);

        foreach(EntityUtil::getWithinRange($this->player->getPosition(), 9.0) as $entity){
            if (MonsterData::isMonster($entity)){
                $xz = 6.0;
                $y = 2.0;
                if (MonsterData::equal($entity, MonsterData::ATTACKER)){
                    $xz = 2.0;
                    $y = 1.0;
                }

                $source = new EntityDamageByEntityEvent($this->player, $entity, EntityDamageByEntityEvent::CAUSE_ENTITY_ATTACK, 6.0);

                EntityUtil::attackEntity($source, $xz, $y);
            }
        }

        return ActionResult::SUCCEEDED();
    }
}