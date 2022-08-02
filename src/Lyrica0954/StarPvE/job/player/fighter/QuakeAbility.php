<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\job\player\fighter;

use Lyrica0954\MagicParticle\LineParticle;
use Lyrica0954\MagicParticle\ParticleOption;
use Lyrica0954\MagicParticle\SingleParticle;
use Lyrica0954\StarPvE\game\wave\MonsterData;
use Lyrica0954\StarPvE\identity\Identity;
use Lyrica0954\StarPvE\job\Ability;
use Lyrica0954\StarPvE\job\AbilityStatus;
use Lyrica0954\StarPvE\job\ActionResult;
use Lyrica0954\StarPvE\StarPvE;
use Lyrica0954\StarPvE\translate\DescriptionTranslator;
use Lyrica0954\StarPvE\utils\EntityUtil;
use Lyrica0954\StarPvE\utils\ParticleUtil;
use Lyrica0954\StarPvE\utils\PlayerUtil;
use Lyrica0954\StarPvE\utils\TaskUtil;
use Lyrica0954\StarPvE\utils\VectorUtil;
use pocketmine\block\Block;
use pocketmine\block\BlockFactory;
use pocketmine\block\BlockLegacyIds;
use pocketmine\entity\Entity;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityRegainHealthEvent;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Vector3;
use pocketmine\scheduler\ClosureTask;
use pocketmine\scheduler\Task;
use pocketmine\scheduler\TaskHandler;
use pocketmine\world\particle\BlockBreakParticle;
use pocketmine\world\particle\Particle;

class QuakeAbility extends Ability {

    public function getCooltime(): int {
        return (int) (1.5 * 20);
    }

    public function getName(): string {
        return "クエイク";
    }

    public function getDescription(): string {
        $area = DescriptionTranslator::number($this->area, "m");
        return
            sprintf('§b発動時:§f 視線の先 %1$s の敵を引き寄せて、 (§c%2$s §fx §cコンボ数§f)  のダメージを与える。', $area, $this->percentage->get());
    }

    protected function init(): void {
        $this->percentage = new AbilityStatus(0.5);
        $this->area = new AbilityStatus(6.0);
    }

    protected function onActivate(): ActionResult {
        $world = $this->player->getWorld();

        #変更: MemoryEntity のaabbを変更してそれの衝突を調べる。-> 速度もあるからよりリアルっぽくなる
        $area = $this->area->get();
        $nearest = null;
        $nearestDist = PHP_INT_MAX;
        foreach (EntityUtil::getLineOfSight($this->player, $this->area->get(), new Vector3(0.3, 0.3, 0.3)) as $result) {
            $entity = $result->getEntity();
            if (MonsterData::isMonster($entity)) {
                $dist = $entity->getPosition()->distance($this->player->getPosition());
                if ($dist < $nearestDist) {
                    $nearest = $entity;
                    $nearestDist = $dist;
                }
            }
        }

        if ($nearest instanceof Entity && MonsterData::isMonster($nearest)) {
            PlayerUtil::playSound($this->player, "use.chain", 0.7, 1.0);
            PlayerUtil::playSound($this->player, "mob.irongolem.crack", 0.8, 0.4);
            $damage = $this->percentage->get();
            $job = $this->getJob();
            if ($job instanceof Fighter) {
                $damage *= $job->getCombo();
            }
            $source = new EntityDamageByEntityEvent($this->player, $nearest, EntityDamageByEntityEvent::CAUSE_ENTITY_ATTACK, $damage, [], 0);
            $dir = $this->player->getDirectionPlane()->multiply(1.5);
            $pos = $this->player->getPosition()->add($dir->x, $this->player->getEyeHeight() - 0.2, $dir->y);
            $entity->teleport($pos);
            $source->setAttackCooldown(0);
            $entity->attack($source);

            #$heal = new EntityRegainHealthEvent($this->player, 2, EntityRegainHealthEvent::CAUSE_CUSTOM);
            #$this->player->heal($heal);
        }

        return ActionResult::SUCCEEDED();
    }
}
