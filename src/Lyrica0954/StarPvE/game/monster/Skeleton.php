<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\game\monster;

use Lyrica0954\MagicParticle\SingleParticle;
use Lyrica0954\SmartEntity\entity\fightstyle\FollowStyle;
use Lyrica0954\SmartEntity\entity\fightstyle\MeleeStyle;
use Lyrica0954\SmartEntity\entity\fightstyle\Style;
use Lyrica0954\SmartEntity\entity\walking\Skeleton as SmartSkeleton;
use Lyrica0954\SmartEntity\SmartEntity;
use Lyrica0954\StarPvE\entity\MemoryEntity;
use Lyrica0954\StarPvE\utils\EntityUtil;
use Lyrica0954\StarPvE\utils\HealthBarEntity;
use Lyrica0954\StarPvE\utils\PlayerUtil;
use Lyrica0954\StarPvE\utils\VectorUtil;
use pocketmine\entity\animation\ArmSwingAnimation;
use pocketmine\entity\Entity;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\math\Vector2;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;
use pocketmine\player\Player;
use pocketmine\Server;

class Skeleton extends SmartSkeleton {
    use HealthBarEntity;

    protected ?MemoryEntity $spark = null;

    protected function getInitialFightStyle(): Style{
        return new MeleeStyle($this);
    }

    public function attackEntity(Entity $entity, float $range): bool{
        if ($this->isAlive() && $range <= $this->getAttackRange() && $this->attackCooldown <= 0){
            $this->broadcastAnimation(new ArmSwingAnimation($this));
            $this->fireElectricSpark($entity, 20, 0.3);
            $this->attackCooldown = 10 + $this->getAddtionalAttackCooldown();

            $this->hitEntity($entity, $range);
            return true;
        } else {
            return false;
        }
    }

    protected function fireElectricSpark(Entity $entity, float $maxRange, float $speed){
        if ($this->spark === null){
            $loc = $this->getLocation();
            $eloc = $entity->getLocation();
            $loc->y += $this->getEyeHeight();
            $this->spark = new MemoryEntity($loc, null, 0.0, 0.0);
            $dx = $eloc->x - $loc->x;
            $dz = $eloc->z - $loc->z;
            $v = (new Vector3($dx, 0, $dz))->normalize();
            $this->spark->setMotion($v->multiply($speed));

            $startTick = Server::getInstance()->getTick();
            $this->spark->addTickHook(function(MemoryEntity $entity) use ($loc, $maxRange, $startTick){
                $ct = Server::getInstance()->getTick();
                if ($entity->getPosition()->distance($loc) >= $maxRange || ($ct - $startTick) >= 30){
                    $entity->close();
                    $this->spark = null;
                    $this->attackCooldown = 20;
                    return;
                }

                foreach(EntityUtil::getPlayersInsideVector($entity->getPosition(), new Vector3(0.4, 0.4, 0.4)) as $player){
                    PlayerUtil::playSound($player, "fireworks.blast", 2.4, 1.0);
                    $source = new EntityDamageByEntityEvent($entity, $player, EntityDamageByEntityEvent::CAUSE_ENTITY_ATTACK, $this->getAttackDamage());
                    EntityUtil::attackEntity($source, 2.25, 1.0);
                }

                if ($ct % 2 == 0){
                    $par = new SingleParticle();
                    $par->sendToPlayers($entity->getWorld()->getPlayers(), $entity->getPosition(), "minecraft:balloon_gas_particle");
                    PlayerUtil::broadcastSound($entity, "firework.twinkle", 1.75, 0.3);
                }
            });
        }
    }
}