<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\job\player\swordman;

use Lyrica0954\StarPvE\game\wave\MonsterData;
use Lyrica0954\StarPvE\job\Ability;
use Lyrica0954\StarPvE\job\AbilityStatus;
use Lyrica0954\StarPvE\job\ActionResult;
use Lyrica0954\StarPvE\job\ticking\Ticking;
use Lyrica0954\StarPvE\job\ticking\TickingController;
use Lyrica0954\StarPvE\StarPvE;
use Lyrica0954\StarPvE\translate\DescriptionTranslator;
use Lyrica0954\StarPvE\utils\EntityUtil;
use Lyrica0954\StarPvE\utils\PlayerUtil;
use Lyrica0954\StarPvE\utils\VectorUtil;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerToggleSneakEvent;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\LevelEventPacket;
use pocketmine\network\mcpe\protocol\types\ActorEvent;
use pocketmine\network\mcpe\protocol\types\ParticleIds;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\world\particle\ExplodeParticle;

class LeapAbility extends Ability implements Ticking, Listener{
    use TickingController;

    private array $damaged = [];

    private bool $activeMotion = false;
    private Vector3 $activateAngle;

    private int $sneakTick;
    
    public function getCooltime(): int{
        return (5 * 20) + 10;
    }

    public function getName(): string{
        return "リープ";
    }

    public function getDescription(): String{
        $area = DescriptionTranslator::number($this->area, "m");
        $damage = DescriptionTranslator::health($this->damage);
        return 
sprintf('§b発動時:§f 視線の先に向かってジャンプする。
§b着地中:§f ジャンプの高さが低く飛距離が長い
§b空中:§f ジャンプの高さが高く飛距離は少し短い

ジャンプ中、周りに風を発生させ %1$s 以内の敵全てを前方に吹き飛ばし、%2$s のダメージを与える。

§b着地中 §fで発動した場合、地面に着地する直前にスニークをするとスライディングすることができる。
スライディングに成功すると、クールタイムが §c1.5秒§f 短くなる。', $area, $damage);
    }

    protected function init(): void{
        $this->damage = new AbilityStatus(3.0);
        $this->area = new AbilityStatus(3.5);
        $this->activateAngle = new Vector3(0, 0, 0);
        $this->sneakTick = 0;
        Server::getInstance()->getPluginManager()->registerEvents($this, StarPvE::getInstance());
    }

    protected function onActivate(): ActionResult{
        $motion = VectorUtil::getDirectionHorizontal($this->player->getLocation()->yaw);
        $this->activateAngle = clone $motion;
        if ($this->player->isOnGround()){
            $motion = $motion->multiply(3.5);
            $motion->y = 0.6;
        } else {
            $motion = $motion->multiply(1.8);
            $motion->y = 1.05;
        }
        $this->player->setMotion($motion);
        $this->startTicking("leapDamage", 1);

        return ActionResult::SUCCEEDED();
    }

    public function onSneak(PlayerToggleSneakEvent $event): void{
        $player = $event->getPlayer();
        if ($this->player == $player){
            if ($event->isSneaking()){
                $this->sneakTick = Server::getInstance()->getTick();
            }
        }
    }

    public function onTick(string $id, int $tick): void{
        if ($id === "leapDamage"){
            if ($this->activeMotion){
                if ($this->player->isOnGround() || $tick >= 45 || $this->player->getFallDistance() >= 2.5){
                    $tick = Server::getInstance()->getTick();
                    if (abs($this->sneakTick - $tick) <= 7 && $this->player->isOnGround()){
                        PlayerUtil::playSound($this->player, "item.trident.hit_ground", 1.7, 1.0);
                        $cooltimeHandler = $this->getCooltimeHandler();
                        if ($cooltimeHandler->isActive()){
                            $cooltimeHandler->subtract($cooltimeHandler->calculate(30));
                        }
                        $motion = $this->player->getDirectionVector()->multiply(4);
                        $this->player->setMotion($motion);
                    }
                    $this->activeMotion = false;
                    $this->damaged = [];
                    $this->stopTicking($id);
                } else {
                    PlayerUtil::broadcastSound($this->player->getPosition(), "ui.cartography_table.take_result", 1.15, 0.6);
    
                    $particle = new ExplodeParticle();
                    $this->player->getWorld()->addParticle($this->player->getPosition(), $particle);
    
                    foreach(EntityUtil::getWithinRange($this->player->getPosition(), $this->area->get()) as $entity){
                        if (MonsterData::isMonster($entity)){
                            $hash = spl_object_hash($entity);
                            if (!in_array($hash, $this->damaged, true)){
                                $this->damaged[] = $hash;
                                $xz = 2.5;
                                $y = 1.1;
                                if (MonsterData::equal($entity, MonsterData::ATTACKER)){
                                    $xz = 0.9;
                                    $y = 1.0;
                                }
    
                                $source = new EntityDamageByEntityEvent($this->player, $entity, EntityDamageByEntityEvent::CAUSE_ENTITY_ATTACK, $this->damage->get());
                                $source->setAttackCooldown(0);
    
                                EntityUtil::attackEntity($source, $xz, $y);
                            }
                        }
                    }
                }
            } else {
                if (!$this->player->isOnGround()){
                    $this->activeMotion = true;
                }

                if ($tick >= 20){
                    $this->stopTicking($id);
                }
            }
        }
    }

}