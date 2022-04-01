<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\service\indicator;

use Lyrica0954\Service\Service;
use Lyrica0954\StarPvE\service\ListenerService;
use Lyrica0954\StarPvE\StarPvE;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\HandlerListManager;
use pocketmine\event\Listener;
use pocketmine\player\Player;

class InboundDamageService extends ListenerService {

    /**
     * @var array{playerHash: string, originalEvent: EntityDamageByEntityEvent}
     */
    private array $originalStore;

    protected function init(): void{
        $this->originalStore = [];
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

                $entity->sendMessage("§c§l<< §r§8{$originalEvent->getOriginalBaseDamage()} §f-> §7{$originalDamage} §f-> §c{$finalDamage} §d({$diffString})");
            }
        }
    }
}