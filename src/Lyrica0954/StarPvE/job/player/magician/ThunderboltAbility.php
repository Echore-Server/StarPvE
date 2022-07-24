<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\job\player\magician;

use Lyrica0954\MagicParticle\EmitterParticle;
use Lyrica0954\MagicParticle\LineParticle;
use Lyrica0954\MagicParticle\ParticleOption;
use Lyrica0954\StarPvE\game\wave\MonsterData;
use Lyrica0954\StarPvE\job\Ability;
use Lyrica0954\StarPvE\job\AbilityStatus;
use Lyrica0954\StarPvE\job\ActionResult;
use Lyrica0954\StarPvE\job\ticking\Ticking;
use Lyrica0954\StarPvE\job\ticking\TickingController;
use Lyrica0954\StarPvE\particle\ElectricSparkParticle;
use Lyrica0954\StarPvE\translate\DescriptionTranslator;
use Lyrica0954\StarPvE\utils\EntityUtil;
use Lyrica0954\StarPvE\utils\PlayerUtil;
use Lyrica0954\StarPvE\utils\RandomUtil;
use Lyrica0954\StarPvE\utils\RayTraceEntityResult;
use Lyrica0954\StarPvE\utils\VectorUtil;
use pocketmine\block\BlockLegacyIds;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\entity\Entity;
use pocketmine\entity\Living;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\math\Facing;
use pocketmine\math\RayTraceResult;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\LevelEventPacket;
use pocketmine\network\mcpe\protocol\types\ActorEvent;
use pocketmine\network\mcpe\protocol\types\ParticleIds;
use pocketmine\world\particle\BlockForceFieldParticle;
use pocketmine\world\particle\ExplodeParticle;
use pocketmine\world\particle\FlameParticle;
use pocketmine\world\Position;
use Ramsey\Uuid\Type\Integer;

class ThunderboltAbility extends Ability implements Ticking {
    use TickingController;

    protected AbilityStatus $chainDamage;

    private array $damaged = [];

    private ?Living $boltTarget = null;
    private ?RayTraceEntityResult $boltHitResult = null;
    private int $chainCount = 0;


    public function getCooltime(): int {
        return (int) ($this->job->getSkill()->isActive() ? (0.35 * 20) : (0.7 * 20)); #スキル依存
    }

    public function getName(): string {
        return "サンダーボルト";
    }

    public function getDescription(): String {
        $damage = DescriptionTranslator::health($this->damage);
        $duration = DescriptionTranslator::second($this->duration);
        $area = DescriptionTranslator::number($this->area, "m");
        $amount = DescriptionTranslator::number($this->amount, "回");
        $chainDamage = DescriptionTranslator::health($this->chainDamage);

        return
            sprintf('§b発動時:§f 視線の先に§e稲妻§fを放つ。
稲妻が敵に当たった場合、その敵に %1$s のダメージを与えて %2$s 動けなくする。
その敵の %3$s 以内に別の敵がいた場合は、その敵にも§e稲妻§fが回っていく(チェイン)。
チェインによって与えられるダメージは %4$s で、最大 %5$s までチェインできる。', $damage, $duration, $area, $chainDamage, $amount);
    }

    protected function init(): void {
        $this->damage = new AbilityStatus(6.0);
        $this->duration = new AbilityStatus(3);
        $this->chainDamage = new AbilityStatus(4.0);
        $this->amount = new AbilityStatus(6);
        $this->area = new AbilityStatus(8.5);
    }

    public function getChainDamage(): AbilityStatus {
        return $this->chainDamage;
    }

    protected function onActivate(): ActionResult {
        $ep = $this->player->getEyePos();
        $epos = new Position($ep->x, $ep->y, $ep->z, $this->player->getWorld());
        $par = new LineParticle($epos, 2);
        $dir = $this->player->getDirectionVector()->multiply(14.0);
        $tpos = $ep->addVector($dir);

        $par->sendToPlayers(
            $this->player->getWorld()->getPlayers(),
            new Position(
                $tpos->x,
                $tpos->y,
                $tpos->z,
                $this->player->getWorld()
            ),
            ParticleOption::spawnPacket("starpve:magician_gas", "")
        );
        $results = EntityUtil::getLineOfSight($this->player, 14.0, new Vector3(0.5, 0.5, 0.5));
        if (count($results) > 0) {
            $result = $results[array_key_first($results)] ?? null;
            if ($result instanceof RayTraceEntityResult) {
                $this->boltTarget = $result->getEntity();
                $this->boltHitResult = $result;
                $this->active = true;
                $this->startTicking("chain", 1);
                return ActionResult::SUCCEEDED_SILENT();
            }
        }


        return ActionResult::ABANDONED();
    }

    public function activate(): ActionResult {
        if (!$this->closed) {
            if (!$this->cooltimeHandler->isActive()) {
                if (!$this->active) {
                    $this->cooltimeHandler->start($this->getCooltime());

                    return $this->onActivate();
                } else {
                    return ActionResult::FAILED_ALREADY_ACTIVE();
                }
            } else {
                return ActionResult::SUCCEEDED_SILENT(); #todo: 
            }
        } else {
            throw new \Exception("cannot activate closed ability");
        }
    }

    private function resetState(): void {
        $this->chainCount = 0;
        $this->boltTarget = null;
        $this->boltHitResult = null;
        $this->damaged = [];
    }

    public function onTick(string $id, int $tick): void {
        if ($id === "chain") {
            if ($this->chainCount >= ($this->amount->get())) {
                $this->active = false;
                $this->resetState();
                $this->stopTicking($id);
            } else {
                $hv = $this->boltHitResult->getHitVector();
                $hitEntity = $this->boltHitResult->getEntity();
                $hitPos = new Position($hv->x, $hv->y, $hv->z, $hitEntity->getWorld());
                $ne = EntityUtil::getNearestMonsterWithout($hitPos, $this->damaged, $this->area->get());
                if ($ne instanceof Living) {
                    $par = new LineParticle($hitPos, 2);
                    $nextPos = $ne->getPosition();
                    $randHeight = $ne->size->getHeight() / 4;
                    $nextPos->y += $ne->size->getEyeHeight();
                    $nextPos->y += RandomUtil::rand_float(-$randHeight, $randHeight);
                    $par->sendToPlayers($hitEntity->getWorld()->getPlayers(), $nextPos, ParticleOption::spawnPacket("starpve:magician_gas", ""));

                    PlayerUtil::broadcastSound($nextPos, "random.glass", 0.8 + ($this->chainCount * 0.15), 1.0);
                    $damage = $this->chainCount == 0 ? $this->damage->get() : $this->chainDamage->get();
                    $source = new EntityDamageByEntityEvent($this->player, $ne, EntityDamageByEntityEvent::CAUSE_ENTITY_ATTACK, $damage);
                    $source->setAttackCooldown(0);
                    EntityUtil::attackEntity($source, 0, 0);

                    EntityUtil::immobile($ne, (int) $this->duration->get());
                    $min = EntityUtil::getCollisionMin($ne);
                    $emitter = EmitterParticle::createEmitterForEntity($ne, 0.3, 4);
                    $emitter->sendToPlayers(
                        $ne->getWorld()->getPlayers(),
                        VectorUtil::insertWorld($min, $ne->getWorld()),
                        ParticleOption::spawnPacket("minecraft:sparkler_emitter", "")
                    );

                    $this->damaged[] = spl_object_hash($ne);

                    $this->chainCount++;
                    $this->boltTarget = $ne;
                    $this->boltHitResult = new RayTraceEntityResult($ne, $this->boltHitResult->getHitFace(), $nextPos);
                } else {
                    $this->chainCount = PHP_INT_MAX; #hack
                }
            }
        }
    }
}
