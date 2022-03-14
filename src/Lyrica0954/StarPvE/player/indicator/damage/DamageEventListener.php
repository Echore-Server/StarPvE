<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\player\indicator\damage;

use Lyrica0954\StarPvE\StarPvE;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\Listener;
use pocketmine\player\Player;

class DamageEventListener implements Listener {

    /**
     * @var array{playerHash: string, originalEvent: EntityDamageByEntityEvent}
     */
    private array $originalStore;

    public function __construct(StarPvE $plugin){
        $this->originalStore = [];
        $plugin->getServer()->getPluginManager()->registerEvents($this, $plugin);
    }


    /**
     * @param EntityDamageByEntityEvent $event
     * 
     * @return void
     * 
     * @priority LOWEST
     */
    public function originalEvent(EntityDamageByEntityEvent $event): void{
        $entity = $event->getEntity();
        if ($entity instanceof Player){
            $this->originalStore[spl_object_hash($entity)] = clone $event;
        }
    }

    /**
     * @param EntityDamageByEntityEvent $event
     * 
     * @return void
     * 
     * @priority MONITOR
     */
    public function finalEvent(EntityDamageByEntityEvent $event): void{
        $entity = $event->getEntity();
        
        if ($entity instanceof Player){
            $originalEvent = $this->originalStore[spl_object_hash($entity)] ?? null;
            if ($originalEvent instanceof EntityDamageByEntityEvent){
                $originalDamage = $originalEvent->getFinalDamage();

                $finalDamage = $event->getFinalDamage();

                $diff = ($finalDamage - $originalDamage);

                $diffString = ($diff >= 0 ? "+" : "") . (string) $diff;

                $entity->sendMessage("§c§l<< §r§7{$originalDamage} §f-> §c{$finalDamage} §d({$diffString})");
            }
        }
    }
}