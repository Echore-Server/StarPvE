<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\job\player\engineer\entity;

use Lyrica0954\MagicParticle\LineParticle;
use Lyrica0954\MagicParticle\SingleParticle;
use Lyrica0954\MagicParticle\SphereParticle;
use Lyrica0954\StarPvE\entity\item\GhostItemEntity;
use Lyrica0954\StarPvE\entity\MemoryEntity;
use Lyrica0954\StarPvE\game\monster\Attacker;
use Lyrica0954\StarPvE\game\monster\Creeper;
use Lyrica0954\StarPvE\game\wave\MonsterData;
use Lyrica0954\StarPvE\utils\EntityUtil;
use Lyrica0954\StarPvE\utils\PlayerUtil;
use Lyrica0954\StarPvE\utils\VectorUtil;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\math\Vector2;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;

class ShieldBall extends GhostItemEntity {

    protected bool $preparing = false;
    protected bool $active = false;

    protected float $power = 0.0;
    protected int $tick = 0;

    protected int $attackTick = 0;
    protected int $particleTick = 0;
    public int $lossPeriod = 1 * 20;

    protected function initEntity(CompoundTag $nbt): void{
        parent::initEntity($nbt);

        $this->setNameTagAlwaysVisible(true);
        $this->setNameTagVisible(true);
    }

    protected function updateNameTag(): void{
        $color = match(true){
            $this->power <= 10 => "§4",
            $this->power <= 25 => "§c",
            $this->power <= 50 => "§6",
            $this->power <= 75 => "§2",
            $this->power <= 100 => "§a",
            default => "§f"
        };

        $nameTag = "§7Power: ". $color . round($this->power) . "%";
        $this->setNameTag($nameTag);
    }

    public function getShieldRadius(): float{
        return 3 + $this->power * 0.07;
    }

    public function entityBaseTick(int $tickDiff = 1): bool{
        $hasUpdate = parent::entityBaseTick($tickDiff);



        if ($this->isOnGround() && (!$this->preparing && !$this->active)){
            $this->preparing = true;
            $this->setMotion(new Vector3(0, 0, 0));
            $this->gravity = 0.0;
            $this->drag = 0.0;

            # 広がってから引き寄せるパーティクル
            (new SingleParticle())->sendToPlayers($this->getWorld()->getPlayers(), $this->getPosition(), "starpve:gravity_ball_prepare");
        }

        if ($this->preparing || $this->active) {
            $this->tick += $tickDiff;
            $this->updateNameTag();
        } else {
            (new SingleParticle)->sendToPlayers(
                $this->getWorld()->getPlayers(),
                VectorUtil::insertWorld(
                    $this->getOffsetPosition(
                        $this->getPosition()
                    ),
                    $this->getWorld()
                ),
                "minecraft:balloon_gas_particle"
            );
        }

        if ($this->preparing){
            if ($this->power < 100){
                $this->power = min(100, ($this->power + $tickDiff));
                PlayerUtil::broadcastSound($this, "block.blastfurnace.fire_crackle", 1.5, 0.9);
            } elseif ($this->power >= 100 && !$this->active){
                $this->power = 100.0;
                $this->active = true;
                $this->preparing = false;
            }
        }

        if ($this->active){
            if ($this->power <= 0){
                (new SingleParticle)->sendToPlayers($this->getWorld()->getPlayers(), $this->getPosition(), "minecraft:knockback_roar_particle");
                PlayerUtil::broadcastSound($this, "block.lantern.break", 0.8);

                $this->kill();
            } else {
                $this->power -= $tickDiff / $this->lossPeriod;

                $this->attackTick += $tickDiff;
                $this->particleTick += $tickDiff;

                if ($this->attackTick >= 3){
                    $this->attackTick = 0;
                    $radius = $this->getShieldRadius();
                    foreach(EntityUtil::getWithinRange($this->getPosition(), $radius) as $entity){
                        if (MonsterData::isMonster($entity)){
                            $kb = new Vector2(3.5, 0.0);
                            if ($entity instanceof Attacker){
                                $kb->x = 1.25;
                                $this->power -= 0.09;
                            } elseif ($entity instanceof Creeper){
                                $entity->explode();
                                $this->power -= 2.0;
                            }

                            if (!$entity->isOnGround()){
                                $kb = $kb->multiply(0.4);
                            }
                            $motion = EntityUtil::modifyKnockback($entity, $this, $kb->x, $kb->y);
                            $entity->setMotion($motion);
                        } elseif ($entity instanceof MemoryEntity){
                            $entity->close();
                        }
                    }
                }

                if ($this->particleTick >= 15){
                    $this->particleTick = 0;
                    PlayerUtil::broadcastSound($this, "beacon.ambient", 1.0, 0.75);
                    
                    $radius = $this->getShieldRadius();
                    for ($h = 0.75; $h < $radius; $h += 0.75){
                        (new SingleParticle)->sendToPlayers($this->getWorld()->getPlayers(), VectorUtil::keepAdd($this->getPosition(), 0, $h, 0), "minecraft:shulker_bullet");
                    }
                    $par = new SphereParticle($radius, 6, 6, 360, -90, 0);
                    $par->sendToPlayers($this->getWorld()->getPlayers(), $this->getPosition(), "minecraft:obsidian_glow_dust_particle");
                }
                
            }
        }

        return $hasUpdate;
    }
}