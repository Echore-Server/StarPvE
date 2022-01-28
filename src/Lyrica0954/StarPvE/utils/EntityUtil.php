<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\utils;

use Generator;
use Lyrica0954\StarPvE\game\wave\MonsterData;
use pocketmine\entity\Attribute;
use pocketmine\entity\Entity;
use pocketmine\entity\Living;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\math\RayTraceResult;
use pocketmine\math\Vector3;
use pocketmine\world\Position;

class EntityUtil {

    public static function getWithinRange(Position $pos, float $range): mixed{
        $entities = [];
        foreach($pos->getWorld()->getEntities() as $entity){
            if ($entity->getPosition()->distance($pos) <= $range){
                $entities[] = $entity;
            }
        }
        return $entities;
    }

    public static function findWithinRange(Position $pos, float $range): Generator{
        foreach($pos->getWorld()->getEntities() as $entity){
            if ($entity->getPosition()->distance($pos) <= $range){
                yield $entity;
            }
        }
    }

    public static function getNearest(Position $pos, float $maxDistance = PHP_INT_MAX){
        $ndist = $maxDistance;
        $nent = null;

        foreach($pos->getWorld()->getEntities() as $entity){
            $dist = $entity->getPosition()->distance($pos);
            if ($dist < $ndist){
                $nent = $entity;
                $ndist = $dist;
            }
        }

        return $nent;
    }

    
    public static function getNearestMonster(Position $pos, float $maxDistance = PHP_INT_MAX){
        $ndist = $maxDistance;
        $nent = null;

        foreach($pos->getWorld()->getEntities() as $entity){
            if (MonsterData::isMonster($entity)){
                $dist = $entity->getPosition()->distance($pos);
                if ($dist < $ndist){
                    $nent = $entity;
                    $ndist = $dist;
                }
            }
        }

        return $nent;
    }

    /**
     * @param Position $pos
     * @param string[] $without
     * @param float $maxDistance
     * 
     * @return Entity|null
     */
    public static function getNearestMonsterWithout(Position $pos, array $without, float $maxDistance = PHP_INT_MAX): ?Entity{
        $ndist = $maxDistance;
        $nent = null;

        foreach($pos->getWorld()->getEntities() as $entity){
            if (MonsterData::isMonster($entity)){
                if (!in_array(spl_object_hash($entity), $without)){
                    $dist = $entity->getPosition()->distance($pos);
                    if ($dist < $ndist){
                        $nent = $entity;
                        $ndist = $dist;
                    }
                }
            }
        }

        return $nent;
    }

    public static function getRandomWithinRange(Position $pos, float $range): ?Entity{
        $entities = self::getWithinRange($pos, $range);
        if (count($entities) > 0){
            return $entities[array_rand($entities)] ?? null;
        } else {
            return null;
        }
    }

    /**
     * @param Entity $entity
     * @param float $reach
     * 
     * @return RayTraceEntityResult[]
     */
    public static function getLineOfSight(Entity $entity, float $reach): array{
        $dir = $entity->getDirectionVector();
        $min = $entity->getEyePos();
        $max = $min->addVector($dir->multiply($reach));

        $entities = [];
        foreach($entity->getWorld()->getEntities() as $target){
            if ($entity !== $target){
                if ($target instanceof Living){
                    $result = $target->getBoundingBox()->calculateIntercept($min, $max);

                    if ($result instanceof RayTraceResult){
                        $entities[] = new RayTraceEntityResult($target, $result->getHitFace(), $result->getHitVector());
                    }
                }
            }
        }

        return $entities;
    }

    public static function modifyKnockback(Entity $entity, Entity $attacker, float $xz = 1.0, float $y = 1.0){
        $epos = $entity->getPosition();
        $apos = $attacker->getPosition();
        $deltaX = $epos->x - $apos->x;
        $deltaZ = $epos->z - $apos->z;
        $motion = self::calculateKnockback($entity, $deltaX, $deltaZ);
        $motion->x *= $xz;
        $motion->y *= $y;
        $motion->z *= $xz;
        return $motion;
    }

    public static function attackEntity(EntityDamageByEntityEvent $source, float $xz = 1.0, float $y = 1.0){
        $source->setKnockBack(0);
        $entity = $source->getEntity();
        $damager = $source->getDamager();

        $kb = self::modifyKnockback($entity, $damager, $xz, $y);

        $entity->attack($source);
        $entity->setMotion($kb);
    }

    public static function calculateKnockback(Entity $entity, float $x, float $z, float $base = 0.4){
        $f = sqrt($x * $x + $z * $z);
        if($f <= 0){
            return new Vector3(0, 0, 0);
        }
        if(mt_rand() / mt_getrandmax() > $entity->getAttributeMap()->get(Attribute::KNOCKBACK_RESISTANCE)->getValue()){
            $f = 1 / $f;
 
            $motion = clone $entity->getMotion();
 
            $motion->x /= 2;
            $motion->y /= 2;
            $motion->z /= 2;
            $motion->x += $x * $f * $base;
            $motion->y += $base;
            $motion->z += $z * $f * $base;
 
            if($motion->y > $base){
                $motion->y = $base;
            }

            return $motion;
        } else {
            return new Vector3(0, 0, 0);
        }
    }

}