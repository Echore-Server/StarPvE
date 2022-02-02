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

class Skeleton extends SmartSkeleton {
    use HealthBarEntity;

    protected ?MemoryEntity $spark = null;

    public function attackEntity(Entity $entity, float $range): bool{
        if ($this->isAlive() && $range <= $this->getAttackRange() && $this->attackCooldown <= 0){
            $this->broadcastAnimation(new ArmSwingAnimation($this));
            $this->fireElectricSpark($entity, 10, 0.75);
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
            $dx = $loc->x - $eloc->x;
            $dz = $loc->z - $eloc->z;
            $v = (new Vector3($dx, 0, $dz))->normalize();
            $this->spark->setMotion($v->multiply($speed));

            $this->spark->addTickHook(function() use ($loc, $maxRange){
                if ($this->spark->getPosition()->distance($loc) >= $maxRange){
                    $this->spark->close();
                }
                $par = new SingleParticle();
                $par->sendToPlayers($this->spark->getWorld()->getPlayers(), $this->spark->getPosition(), "minecraft:balloon_gas_particle");
                PlayerUtil::broadcastSound($this->spark, "firework.twinkle", 0.9, 0.4);
            });
        }
    }
}