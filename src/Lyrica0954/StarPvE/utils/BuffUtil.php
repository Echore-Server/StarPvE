<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\utils;

use pocketmine\entity\Entity;
use pocketmine\entity\projectile\Arrow;
use pocketmine\event\entity\EntityDamageByChildEntityEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\Listener;
use pocketmine\plugin\PluginBase;
use pocketmine\Server;

class BuffUtil implements Listener {

    const BUFF_ATK_DAMAGE = 0;
    const BUFF_ATK_PERCENTAGE = 1;
    const BUFF_DMG_REDUCTION = 2;
    const BUFF_DMG_REFLECTION = 3;
    const BUFF_DMG_REDUCTION_PERC = 4;

    const BUFF_FIRE_ASPECT_DURATION = 5;
    const BUFF_ARROW_POWER_PERC = 6;

    public static array $list;

    public function __construct(PluginBase $pluginBase) {
        self::$list = [];
        Server::getInstance()->getPluginManager()->registerEvents($this, $pluginBase);
    }

    public function onEntityDamage(EntityDamageEvent $event): void {
        $entity = $event->getEntity();
        $finalDamage = $event->getFinalDamage();


        if ($event instanceof EntityDamageByEntityEvent) {
            $damager = $event->getDamager();

            EntityUtil::addFinalDamage($event, self::get($damager, self::BUFF_ATK_DAMAGE));
            EntityUtil::multiplyFinalDamage($event, (1.0 + self::get($damager, self::BUFF_ATK_PERCENTAGE)));

            $fireAspectDuration = self::get($damager, self::BUFF_FIRE_ASPECT_DURATION);
            if ($fireAspectDuration > 0.0) {
                $duration = (int) ceil($fireAspectDuration);
                $entity->setOnFire($duration / 20);
            }

            if ($event instanceof EntityDamageByChildEntityEvent) {
                $child = $event->getChild();
                if ($child instanceof Arrow) {
                    EntityUtil::multiplyFinalDamage($event, (1.0 + self::get($damager, self::BUFF_ARROW_POWER_PERC)));
                }
            }
        }

        EntityUtil::addFinalDamage($event, -self::get($entity, self::BUFF_DMG_REDUCTION));
        EntityUtil::multiplyFinalDamage($event, (1.0 - self::get($entity, self::BUFF_DMG_REDUCTION_PERC)));

        if ($event instanceof EntityDamageByEntityEvent) {
            $damager = $event->getDamager();

            if ($finalDamage >= self::get($entity, self::BUFF_DMG_REFLECTION)) {
                $damage = self::get($entity, self::BUFF_DMG_REFLECTION);
                if ($damage > 0.0) {
                    $source = new EntityDamageEvent($damager, EntityDamageEvent::CAUSE_MAGIC, $damage);
                    $damager->attack($source);
                }
            }
        }
    }

    public static function set(Entity $entity, int $buff, float $value): void {
        $h = spl_object_hash($entity);
        self::$list[$h] ?? self::$list[$h] = [];
        self::$list[$h][$buff] = $value;
    }

    public static function get(Entity $entity, int $buff): float {
        return self::$list[spl_object_hash($entity)][$buff] ?? 0.0;
    }

    public static function add(Entity $entity, int $buff, float $add): void {
        self::set($entity, $buff, self::get($entity, $buff) + $add);
    }

    public static function subtract(Entity $entity, int $buff, float $subtract): void {
        self::add($entity, $buff, -$subtract);
    }
}
