<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\job\player\fighter;

use Lyrica0954\MagicParticle\EmitterParticle;
use Lyrica0954\StarPvE\job\AbilityStatus;
use Lyrica0954\StarPvE\job\ActionResult;
use Lyrica0954\StarPvE\job\Skill;
use Lyrica0954\StarPvE\StarPvE;
use Lyrica0954\StarPvE\translate\DescriptionTranslator;
use Lyrica0954\StarPvE\utils\EntityUtil;
use Lyrica0954\StarPvE\utils\PlayerUtil;
use Lyrica0954\StarPvE\utils\TaskUtil;
use Lyrica0954\StarPvE\utils\VectorUtil;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\Listener;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\PlayerFogPacket;
use pocketmine\player\Player;
use pocketmine\scheduler\ClosureTask;
use pocketmine\Server;

class RageSkill extends Skill implements Listener{

    public function getCooltime(): int{
        return (90 * 20);
    }

    public function getName(): String{
        return "レイジ";
    }

    public function getDescription(): String{
        $duration = DescriptionTranslator::second($this->duration);
        $amount = DescriptionTranslator::number($this->amount, "");
        $damage = DescriptionTranslator::health($this->damage, false);
        $damageProt = DescriptionTranslator::percentage($this->percentage, true);
        return
sprintf('発動時: %1$s 間、攻撃速度が %2$s 上昇する(ファイトアップの効果も反映される)
効果中に攻撃された場合、その敵に(%3$s + §eファイトアップのレベル§f)のダメージとノックバックを与えるカウンター攻撃を行い、受けるダメージを %4$s 軽減する。',
$duration, $amount, $damage, $damageProt);
    }

    protected function init(): void{
        $this->duration = new AbilityStatus(40 * 20);
        $this->damage = new AbilityStatus(3.0);
        $this->amount = new AbilityStatus(2);
        $this->percentage = new AbilityStatus(0.8);
    }

    protected function onActivate(): ActionResult{
        PlayerUtil::playSound($this->player, "random.fuse", 0.75);
        PlayerUtil::playSound($this->player, "mob.creeper.death", 0.5, 0.6);
        $fog = PlayerFogPacket::create(["minecraft:fog_hell"]);
        $this->player->getNetworkSession()->sendDataPacket($fog);

        $this->active = true;
        StarPvE::getInstance()->getScheduler()->scheduleDelayedTask(new ClosureTask(function (){
            $fogRemove = PlayerFogPacket::create([]);
            $this->player->getNetworkSession()->sendDataPacket($fogRemove);
            PlayerUtil::playSound($this->player, "random.fizz", 0.5);
            $this->active = false;
        }), (integer) $this->duration->get());


        $period = 2;
        $limit = ((integer) $this->duration->get()) / $period;
        TaskUtil::repeatingClosureLimit(function (){
            $min = EntityUtil::getCollisionMin($this->player);
            $par = EmitterParticle::createEmitterForEntity($this->player, 0.3, 1);

            $par->sendToPlayers(
                $this->player->getWorld()->getPlayers(),
                VectorUtil::insertWorld($min, $this->player->getWorld()),
                "minecraft:villager_angry"
            );
        }, $period, $limit);
        

        return ActionResult::SUCCEEDED();
    }

    public function onEntityDamage(EntityDamageEvent $event){
        $entity = $event->getEntity();
        if ($entity === $this->player && $this->player instanceof Player){
            if ($this->isActive()){
                if ($event instanceof EntityDamageByEntityEvent){
                    $damager = $event->getDamager();
                    $add = 0;
                    if ($this->job instanceof Fighter){
                        $add = $this->job->getComboLevel();
                    }
                    $counterDamage = $this->damage->get() + $add;
                    $source = new EntityDamageByEntityEvent($this->player, $damager, EntityDamageByEntityEvent::CAUSE_ENTITY_ATTACK, $counterDamage);
                    $damager->attack($source);
        
                    PlayerUtil::playSound($this->player, "crossbow.shoot", 1.5, 0.9);
                }
                EntityUtil::multiplyFinalDamage($event, $this->percentage->get());
            }
        }
    }
}