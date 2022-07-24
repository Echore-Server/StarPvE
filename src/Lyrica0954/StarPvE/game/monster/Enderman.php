<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\game\monster;

use Lyrica0954\SmartEntity\entity\fightstyle\MeleeStyle;
use Lyrica0954\SmartEntity\entity\fightstyle\RangedStyle;
use Lyrica0954\SmartEntity\entity\fightstyle\Style;
use Lyrica0954\SmartEntity\entity\Hostile;
use Lyrica0954\SmartEntity\entity\walking\FightingEntity;
use Lyrica0954\SmartEntity\entity\walking\Zombie as SmartZombie;
use Lyrica0954\StarPvE\event\PlayerDeathOnGameEvent;
use Lyrica0954\StarPvE\StarPvE;
use Lyrica0954\StarPvE\utils\EntityUtil;
use Lyrica0954\StarPvE\utils\HealthBarEntity;
use Lyrica0954\StarPvE\utils\PlayerUtil;
use pocketmine\entity\animation\ArmSwingAnimation;
use pocketmine\entity\Entity;
use pocketmine\entity\EntitySizeInfo;
use pocketmine\entity\projectile\ProjectileSource;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityMotionEvent;
use pocketmine\event\HandlerListManager;
use pocketmine\event\Listener;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;
use pocketmine\player\Player;
use pocketmine\Server;



class Enderman extends FightingEntity implements Hostile, Listener {
    use HealthBarEntity;

    protected ?Player $holding = null;

    protected int $holdDamageTick = 0;
    protected int $holdTick = 0;

    protected int $holdRemain = 1;

    public static function getNetworkTypeId(): string {
        return EntityIds::ENDERMAN;
    }

    protected float $reach = 1.5;

    public function getFollowRange(): float {
        return 50;
    }

    public function getName(): string {
        return "Enderman";
    }

    public function getHolding(): ?Player {
        return $this->holding;
    }

    protected function getInitialSizeInfo(): EntitySizeInfo {
        return new EntitySizeInfo(2.9, 0.6);
    }

    protected function getInitialFightStyle(): Style {
        return new MeleeStyle($this);
    }

    public function getAddtionalAttackCooldown(): int {
        return 14;
    }

    public function onPlayerDeath(PlayerDeathOnGameEvent $event) {
        $player = $event->getPlayer();

        if ($player === $this->holding) {
            $this->release();
        }
    }

    protected function onTick(int $currentTick, int $tickDiff = 1): void {
        if ($this->holding !== null) {
            #$this->motion = new Vector3(0, 0, 0);
            $this->holdDamageTick += $tickDiff;
            $this->holdTick += $tickDiff;
            if ($this->holdTick >= (15 * 20)) {
                $this->release();
                return;
            }
            if ($this->holdDamageTick >= 20) {
                $this->holdDamageTick = 0;
                PlayerUtil::broadcastSound($this->holding, "mob.irongolem.crack", 0.8, 1.0);
                $source = new EntityDamageEvent($this->holding, EntityDamageEvent::CAUSE_MAGIC, $this->getAttackDamage());
                $this->holding->attack($source);
            }
        } else {
            $this->holdDamageTick = 0;
            $this->holdTick = 0;
        }
    }

    public function hitEntity(Entity $entity, float $range): void {
        parent::hitEntity($entity, $range);

        if ($entity instanceof Player) {
            PlayerUtil::playSound($entity, "mob.endermen.scream", 1.0, 0.6);

            $this->hold($entity);
        }
    }

    public function attackEntity(Entity $entity, float $range): bool {
        if ($this->isAlive() && $range <= $this->getAttackRange() && $this->attackCooldown <= 0) {
            $this->broadcastAnimation(new ArmSwingAnimation($this));
            if ($this->holdRemain <= 0) {
                $source = new EntityDamageByEntityEvent($this, $entity, EntityDamageByEntityEvent::CAUSE_ENTITY_ATTACK, $this->getAttackDamage());
                $entity->attack($source);
                $this->attackCooldown = $source->getAttackCooldown() + $this->getAddtionalAttackCooldown();
            } else {
                $this->hitEntity($entity, $range);
                $this->attackCooldown = $this->getAddtionalAttackCooldown();
            }
            return true;
        } else {
            return false;
        }
    }

    /**
     * @param EntityDamageEvent $source
     * 
     * @return void
     * 
     * @notHandler
     */
    public function attack(EntityDamageEvent $source): void {
        parent::attack($source);

        $revenge = false;
        if ($source instanceof EntityDamageByEntityEvent) {
            if ($this->holding !== null) {
                if ($source->getDamager() === $this->holding) {
                    $revenge = true;
                }

                $source->setKnockBack(0);
            }
        }

        if (!$source->isCancelled() && !$revenge) {
            $this->release();
        }
    }

    public function onMotion(EntityMotionEvent $event) {
        $entity = $event->getEntity();
        if ($entity === $this) {
            $event->cancel();
        }
    }

    public function release(): void {
        if ($this->holding !== null) {
            $this->holding->setImmobile(false);
            $this->setImmobile(false);
        }

        $this->holding = null;
    }

    public function hold(Player $player): void {
        if ($this->holdRemain <= 0) {
            return;
        }
        $this->holdRemain--;
        $this->holding = $player;
        $this->setImmobile(true);

        $player->setImmobile(true);

        $pos = $this->getPosition();
        $dir = $this->getDirectionPlane();
        $holdPos = $pos->addVector(new Vector3($dir->x, 1.5, $dir->y));
        $player->teleport($holdPos);
    }

    protected function onDispose(): void {
        parent::onDispose();

        HandlerListManager::global()->unregisterAll($this);
    }

    protected function onDeath(): void {
        parent::onDeath();

        $this->release();
    }

    protected function initEntity(CompoundTag $nbt): void {
        parent::initEntity($nbt);

        $game = StarPvE::getInstance()->getGameManager()->getGameFromWorld($this->getWorld());
        if ($game !== null) {
            $this->teleport($game->getCenterPosition());
        }

        Server::getInstance()->getPluginManager()->registerEvents($this, StarPvE::getInstance());
    }
}
