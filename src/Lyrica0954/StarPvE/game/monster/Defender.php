<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\game\monster;

use Lyrica0954\MagicParticle\CircleParticle;
use Lyrica0954\MagicParticle\LineParticle;
use Lyrica0954\MagicParticle\ParticleOption;
use Lyrica0954\SmartEntity\entity\fightstyle\helper\HelpEntity;
use Lyrica0954\SmartEntity\entity\fightstyle\HelperStyle;
use Lyrica0954\SmartEntity\entity\fightstyle\Style;
use Lyrica0954\SmartEntity\entity\Hostile;
use Lyrica0954\SmartEntity\entity\Neutral;
use Lyrica0954\SmartEntity\entity\walking\FightingEntity;
use Lyrica0954\StarPvE\utils\EntityUtil;
use Lyrica0954\StarPvE\utils\HealthBarEntity;
use Lyrica0954\StarPvE\utils\ParticleUtil;
use Lyrica0954\StarPvE\utils\PlayerUtil;
use pocketmine\entity\Entity;
use pocketmine\entity\EntitySizeInfo;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\math\Vector2;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;
use pocketmine\player\Player;
use pocketmine\world\particle\CriticalParticle;

class Defender extends FightingEntity implements Neutral {
    use HelpEntity, HealthBarEntity;

    private float $n = 0;

    protected int $ptick = 0;

    protected int $atick = 0;
    protected int $vtick = 0;

    public static function getNetworkTypeId(): string {
        return EntityIds::DROWNED;
    }

    public function getName(): string {
        return "Defender";
    }

    public function getAddtionalAttackCooldown(): int {
        return 0;
    }

    protected function getInitialSizeInfo(): EntitySizeInfo {
        return new EntitySizeInfo(1.8, 0.6);
    }

    protected function getInitialFightStyle(): Style {
        return new HelperStyle($this);
    }

    public function checkTarget(Entity $entity, float $range): bool {
        return false;
    }

    protected function selectTarget(array $targets): void {
        #助けるけど戦わない
    }

    public function checkCurrentTarget() {
        return false;
    }

    public function getFollowRange(): float {
        return 6;
    }

    public function getAttackRange(): float {
        return 5;
    }

    protected function onTick(int $currentTick, int $tickDiff = 1): void {
        if ($this->getHelping() === null) {
            foreach (EntityUtil::getWithinRange($this->getPosition(), 15) as $entity) {
                if ($entity instanceof Attacker) {
                    $this->setHelping($entity);
                    break;
                }
            }
        } else {
            $helping = $this->getHelping();
            if (!$helping->isAlive() || $helping->isClosed()) {
                $this->setHelping(null);
            } else {
                $this->ptick += $tickDiff;
                $epos = $helping->getPosition();
                $epos->y += 0.6;
                $pos = $this->getPosition();
                $pos->y += 0.6;

                $par = (new LineParticle($pos, 3));
                if ($this->ptick >= 10) {
                    $this->ptick = 0;
                    ParticleUtil::send($par, $this->getWorld()->getPlayers(), $epos, ParticleOption::spawnPacket("starpve:soft_green_gas", ""));
                }
            }
        }

        $this->n += 0.1;
        $pos = $this->getPosition();
        $current = $this->getPosition();
        $current->y += 1.75;
        $current->y += (2.25 * sin($this->n));

        $n = $this->n + 0.1;
        $next = $this->getPosition();
        $next->y += 1.75;
        $next->y += (2.25 * sin($n));
        $velocity = $next->subtractVector($current);

        $this->vtick += $tickDiff;

        if ($this->vtick >= 5) {
            $this->vtick = 0;
            $par = new CircleParticle(5, 6, 0);
            ParticleUtil::send($par, $this->getWorld()->getPlayers(), $current, ParticleOption::spawnPacket("minecraft:basic_crit_particle", ""));
        }

        $this->atick += $tickDiff;
        if ($this->atick >= 2) {
            $this->atick = 0;
            foreach ($this->getWorld()->getEntities() as $entity) {
                if ($entity instanceof Player) {
                    if (!$entity->isSpectator() && $entity->isAlive()) {
                        $epos = $entity->getPosition();
                        $hxz = new Vector3($pos->x, 0, $pos->z);
                        $exz = new Vector3($epos->x, 0, $epos->z);

                        $xzd = $hxz->distance($exz);

                        $minY = $entity->getBoundingBox()->minY;
                        $maxY = $entity->getBoundingBox()->maxY;
                        if ($xzd <= 5 && ($current->y >= $minY && $current->y <= $maxY)) {
                            $source = new EntityDamageByEntityEvent($this, $entity, EntityDamageByEntityEvent::CAUSE_ENTITY_ATTACK, $this->getAttackDamage());
                            $source->setAttackCooldown(4);
                            PlayerUtil::playSound($entity, "mob.player.hurt_freeze", 1.2, 0.9);
                            EntityUtil::attackEntity($source, 0.25, $velocity->y * 8.5);
                        }
                    }
                }
            }
        }
    }
}
