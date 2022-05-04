<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\job\player\fighter;

use Lyrica0954\MagicParticle\SingleParticle;
use Lyrica0954\StarPvE\data\condition\Condition;
use Lyrica0954\StarPvE\data\condition\LevelCondition;
use Lyrica0954\StarPvE\game\wave\MonsterData;
use Lyrica0954\StarPvE\identity\IdentityGroup;
use Lyrica0954\StarPvE\job\Ability;
use Lyrica0954\StarPvE\job\AlwaysAbility;
use Lyrica0954\StarPvE\job\player\PlayerJob;
use Lyrica0954\StarPvE\job\player\swordman\ForceFieldSkill;
use Lyrica0954\StarPvE\job\Skill;
use Lyrica0954\StarPvE\utils\EntityUtil;
use Lyrica0954\StarPvE\utils\MathUtil;
use Lyrica0954\StarPvE\utils\PlayerUtil;
use Lyrica0954\StarPvE\utils\VectorUtil;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\Listener;
use pocketmine\event\server\DataPacketSendEvent;
use pocketmine\network\mcpe\protocol\PlaySoundPacket;
use pocketmine\network\mcpe\protocol\SetTimePacket;
use pocketmine\network\mcpe\protocol\SetTitlePacket;
use pocketmine\Server;

class Fighter extends PlayerJob implements AlwaysAbility, Listener{

    protected int $combo = 0;
    protected int $comboLevel = 0; #0 ~ 6
    protected int $lastAttackTick = 0;

    public function getCombo(): int{
        return $this->combo;
    }

    public function getComboLevel(): int{
        return $this->comboLevel;
    }

    protected function getInitialIdentityGroup(): IdentityGroup{
        return new IdentityGroup();
    }

    protected function getInitialAbility(): Ability{
        return new QuakeAbility($this);
    }

    protected function getInitialSkill(): Skill{
        return new RageSkill($this);
    }

    public function getName(): string{
        return "Fighter";
    }

    public function getDescription(): string{
        return 
"§7- §l§c戦闘§r

己の力のみで戦うファイター。";
    }

    public function getAlAbilityName(): string{
        return "ファイトアップ";
    }

    public function getAlAbilityDescription(): string{
        return 
"発動条件: 敵を殴った際に発動
発動時: コンボが§c1§f増える。
コンボが一定数たまると、攻撃速度が最大6段階まであがる。
最高レベルに達した状態で攻撃した場合、§c3§fコンボに一回小さな爆発を起こす。
小さな爆発は半径§c1.5m§f以内の敵に剣での攻撃と同じダメージを与える。
コンボを§c4秒§f以内につなげないと、コンボがリセットされてしまうので注意。
§7レベルごとの攻撃速度
デフォルトの攻撃速度: 10
レベル0: 11(デフォルトより1遅い)
レベル1: 10
レベル2: 9
レベル3: 8
レベル4: 7
レベル5: 6
レベル6: 5";
    }

    public function getSelectableCondition(): ?Condition{
        return null;
    }

    protected function fixTitle(): void{
        $pk = new SetTitlePacket;
        $pk->type = SetTitlePacket::TYPE_RESET_TITLE;
        $this->player->getNetworkSession()->sendDataPacket($pk);
    }

    public function onDataPacketSend(DataPacketSendEvent $event){
        $packets = $event->getPackets();
        $targets = $event->getTargets();
        if ($this->skill->isActive()){
            foreach($targets as $session){
                if ($session->getPlayer() === $this->player){
                    foreach($packets as $packet){
                        if ($packet instanceof PlaySoundPacket){
                            $packet->volume *= 0.75;
                        }
                    }
                }
            }
        }
    }

    public function onEntityDamageByEntity(EntityDamageByEntityEvent $event){
        $entity = $event->getEntity();
        $damager = $event->getDamager();

        if (!$event->isCancelled()){
            if ($damager === $this->player){
                if (MonsterData::isMonster($entity)){
                    if (true){
                        $tick = Server::getInstance()->getTick();
                        if ($tick - $this->lastAttackTick > 80){
                            $this->combo = 0;
                        }
    
                        $this->combo ++;
                        $delay = match(true){
                            $this->combo >= 19 => 5,
                            $this->combo >= 15 => 6,
                            $this->combo >= 12 => 7,
                            $this->combo >= 9 => 8,
                            $this->combo >= 4 => 9,
                            $this->combo >= 2 => 10,
                            default => 11
                        };
                        $skillAdjust = ($this->getSkill()->isActive() ? ((integer) $this->getSkill()->getAmount()->get()) : 0);
                        $delay -= $skillAdjust;
                        $delay = max(0, $delay);
    
                        $this->comboLevel = (11 - $delay) - $skillAdjust;
                        if ($this->comboLevel >= 6){
                            $this->comboLevel = 6;
                        }
                        $color = "§7";
                        if ($this->comboLevel == 6){
                            $color = ($this->combo % 2 === 0) ? "§c" : "§d";
    
                            if ($this->combo % 3 === 0){
                                $par = new SingleParticle;
                                $pos = VectorUtil::keepAdd($entity->getPosition(), 0, 1.0, 0);
                                $par->sendToPlayers($this->player->getWorld()->getPlayers(), $pos, "minecraft:dragon_destroy_block");
                                PlayerUtil::broadcastSound($entity, "cauldron.explode", 1.3, 1.0); #スキルで聞こえにくくなるから　結局 0.75 になる
                                foreach(EntityUtil::getWithinRange($pos, 1.5) as $exEntity){
                                    if ($exEntity !== $entity){
                                        if (MonsterData::isMonster($exEntity)){
                                            $source = new EntityDamageByEntityEvent($this->player, $exEntity, EntityDamageByEntityEvent::CAUSE_ENTITY_ATTACK, $event->getBaseDamage(), [], 0);
                                            $source->setAttackCooldown(0);
                                            $exEntity->attack($source);
                                        }
                                    }
                                }
                            }
                        }
    
                        $this->player->sendTitle("§r", "                               §c§l{$this->combo} §fCombo\n§r§f                               {$color}Level {$this->comboLevel}", 0, 80, 0);
                        $this->fixTitle();
                        
    
                        $event->setAttackCooldown($delay);
                        $this->lastAttackTick = $tick;
                    }
                }
            }
        }
    }

}