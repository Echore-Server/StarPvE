<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\game\monster;

use Lyrica0954\MagicParticle\ParticleOption;
use Lyrica0954\MagicParticle\SingleParticle;
use Lyrica0954\SmartEntity\entity\fightstyle\MeleeStyle;
use Lyrica0954\SmartEntity\entity\fightstyle\RangedStyle;
use Lyrica0954\SmartEntity\entity\fightstyle\Style;
use Lyrica0954\SmartEntity\entity\Hostile;
use Lyrica0954\SmartEntity\entity\walking\FightingEntity;
use Lyrica0954\SmartEntity\entity\walking\Zombie as SmartZombie;
use Lyrica0954\StarPvE\entity\MemoryEntity;
use Lyrica0954\StarPvE\game\Game;
use Lyrica0954\StarPvE\StarPvE;
use Lyrica0954\StarPvE\utils\EntityUtil;
use Lyrica0954\StarPvE\utils\HealthBarEntity;
use Lyrica0954\StarPvE\utils\ParticleUtil;
use Lyrica0954\StarPvE\utils\PlayerUtil;
use Lyrica0954\StarPvE\utils\RandomUtil;
use Lyrica0954\StarPvE\utils\VectorUtil;
use pocketmine\block\Block;
use pocketmine\data\bedrock\EntityLegacyIds;
use pocketmine\entity\animation\ArmSwingAnimation;
use pocketmine\entity\effect\Effect;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\entity\Entity;
use pocketmine\entity\EntitySizeInfo;
use pocketmine\entity\projectile\ProjectileSource;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityRegainHealthEvent;
use pocketmine\math\RayTraceResult;
use pocketmine\math\Vector3;
use pocketmine\math\VoxelRayTrace;
use pocketmine\network\mcpe\protocol\AddActorPacket;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\world\particle\HugeExplodeParticle;

class PiglinBrute extends FightingEntity implements Hostile, ProjectileSource {
    use HealthBarEntity;

    public static function getNetworkTypeId(): string {
        return EntityIds::PIGLIN_BRUTE;
    }

    protected float $reach = 1.5;

    protected int $effectTick = 0;

    protected int $missileTick = 0;

    protected int $healTick = 0;

    protected bool $awake = false;

    /**
     * @var MemoryEntity[]
     */
    protected array $missiles;

    public function getFollowRange(): float {
        return 50;
    }

    public function getName(): string {
        return "Piglin Brute";
    }

    protected function getInitialSizeInfo(): EntitySizeInfo {
        return new EntitySizeInfo(1.8, 0.6);
    }

    protected function getInitialFightStyle(): Style {
        return new MeleeStyle($this);
    }

    public function getAddtionalAttackCooldown(): int {
        return 4;
    }

    public function fireMissile(int $count): void {
        $game = StarPvE::getInstance()->getGameManager()->getGameFromWorld($this->getWorld());
        $players = $this->getWorld()->getPlayers();
        if ($game instanceof Game) {
            $players = $game->getPlayers();
        }
        $full = 0.6;
        for ($i = 0; $i < $count; $i++) {
            $loc = $this->getLocation();
            $loc->y += $this->getEyeHeight();
            $missile = new MemoryEntity($loc, null, 0.04);

            $v = new Vector3(RandomUtil::rand_float(-$full, $full), 0, RandomUtil::rand_float(-$full, $full));
            $missile->setMotion(new Vector3($v->x, 2.0, $v->z));
            $missile->setKeepMovement(true);

            $missile->addCloseHook(function (MemoryEntity $entity) {
            });

            if (count($players) > 0) {
                $missileTarget = $players[array_rand($players)];
            } else {
                $missileTarget = null;
            }

            $startTick = Server::getInstance()->getTick();

            $store = new \stdClass;
            $store->lastPos = $loc;

            $onHit = function (MemoryEntity $entity) {
                $par = new HugeExplodeParticle();
                $entity->getWorld()->addParticle($entity->getPosition(), $par);

                PlayerUtil::broadcastSound($entity->getPosition(), "random.explode", 0.7, 0.7);

                foreach (EntityUtil::getWithinRange($entity->getPosition(), 4) as $e) {
                    if ($e instanceof Player) {
                        $source = new EntityDamageEvent($e, EntityDamageEvent::CAUSE_ENTITY_EXPLOSION, 5.0);
                        $source->setAttackCooldown(0);
                        $e->attack($source);

                        #$e->getEffects()->add(new EffectInstance(VanillaEffects::SLOWNESS(), 20 *  6, 255));
                    }
                }
            };

            $missile->addTickHook(function (MemoryEntity $entity) use ($loc, $missileTarget, $onHit, $store, $startTick) {
                $ct = Server::getInstance()->getTick();
                $age = ($ct - $startTick);
                if ($age >= 200) {
                    $entity->close();
                    return;
                }

                if ($missileTarget instanceof Entity && $age >= 14 && $missileTarget->isAlive() && !$missileTarget->isClosed()) {
                    $check = true;
                    if ($missileTarget instanceof Player) {
                        if (!$missileTarget->hasFiniteResources()) {
                            $check = false;
                        }
                    }

                    if ($check) {
                        $tpos = $missileTarget->getPosition()->add(0, 0.5, 0);
                        $pos = $entity->getPosition();

                        $curr = $entity->getMotion(); #現在
                        $diff = $pos->subtractVector($tpos); #進みたい

                        $delta = $diff->subtractVector($curr); #現在->進みたいの直線
                        if ($delta->length() >= 0.1) {
                            $deltaNormalized = $delta->normalize();

                            $turnSpeed = 0.04;
                            $all = $deltaNormalized->multiply(-$turnSpeed);

                            $final = $curr->addVector($all);

                            $maxSpeed = 0.5;
                            $final->y = max(-$maxSpeed, $final->y);

                            $entity->setMotion($final);
                        }
                    }
                }


                $start = $store->lastPos;
                $end = $entity->getPosition();

                $blockHit = null;
                $hitResult = null;

                if ($end->distanceSquared($start) > 0.0) {
                    foreach (VoxelRayTrace::betweenPoints($start, $end) as $vector3) {
                        $block = $this->getWorld()->getBlockAt($vector3->x, $vector3->y, $vector3->z);

                        $blockHitResult = $block->calculateIntercept($start, $end);
                        if ($blockHitResult !== null) {
                            $end = $blockHitResult->hitVector;
                            $blockHit = $block;
                            $hitResult = $blockHitResult;
                            break;
                        }
                    }
                }


                $playerHit = false;

                foreach (EntityUtil::getPlayersInsideVector($entity->getPosition(), new Vector3(1.2, 1.2, 1.2)) as $player) {
                    $playerHit = true;
                    break;
                }

                $hit = ($hitResult instanceof RayTraceResult && $blockHit instanceof Block) || $playerHit;

                if ($hit && $age >= 14) {
                    ($onHit)($entity, $blockHit, $hitResult);
                    $entity->close();
                }

                $par = new SingleParticle();
                ParticleUtil::send($par, $entity->getWorld()->getPlayers(), $entity->getPosition(), ParticleOption::spawnPacket("starpve:soft_red_gas", ""));

                $store->lastPos = $entity->getPosition();
            });
        }
    }

    protected function onTick(int $currentTick, int $tickDiff = 1): void {
        $this->effectTick += $tickDiff;
        $this->missileTick += $tickDiff;
        $this->healTick += $tickDiff;

        if (!$this->isAlive()) {
            return;
        }

        if ($this->effectTick >= 40) {
            $this->effectTick = 0;

            foreach (EntityUtil::getWithinRange($this->getPosition(), 10) as $entity) {
                if ($entity instanceof Piglin) {
                    $effect = new EffectInstance(VanillaEffects::SPEED(), 60, 1);
                    $entity->getEffects()->add($effect);
                }
            }
        }

        if ($this->missileTick >= 200) {
            $this->fireMissile(1);

            $count = 10;
            if ($this->awake) {
                $count = 60;
            }

            if ($this->missileTick >= (200 + $count)) {
                $this->missileTick = 0;
            }
        }

        if ($this->getHealth() <= $this->getMaxHealth() * 0.25 && !$this->awake) {
            $this->setMaxHealth($this->getMaxHealth() * 2);
            $this->setHealth($this->getMaxHealth());
            $this->setMovementSpeed($this->getMovementSpeed() * 2);

            PlayerUtil::broadcastSound($this, "random.totem", 1.2, 0.7);

            $pk = AddActorPacket::create(0, Entity::nextRuntimeId(), EntityIds::LIGHTNING_BOLT, $this->getPosition(), Vector3::zero(), 0, 0, 0, 0, [], [], []);
            foreach ($this->getWorld()->getPlayers() as $player) {
                $player->getNetworkSession()->sendDataPacket($pk);
            }
            $this->awake = true;
        }

        $period = 10;
        $amount = 1;
        if ($this->awake) {
            $period = 1;
            $amount = 1.2;
        }

        if ($this->healTick >= $period) {
            $this->healTick = 0;
            $source = new EntityRegainHealthEvent($this, $amount, EntityRegainHealthEvent::CAUSE_CUSTOM);
            $this->heal($source);
        }
    }

    public function hitEntity(Entity $entity, float $range): void {
        if ($entity instanceof Player) {
            PlayerUtil::playSound($entity, "random.break", 0.3, 0.75);
        }
    }
}
