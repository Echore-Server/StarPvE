<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\job\player\fighter;

use Lyrica0954\MagicParticle\LineParticle;
use Lyrica0954\MagicParticle\SingleParticle;
use Lyrica0954\StarPvE\game\wave\MonsterData;
use Lyrica0954\StarPvE\job\Ability;
use Lyrica0954\StarPvE\job\AbilityStatus;
use Lyrica0954\StarPvE\job\ActionResult;
use Lyrica0954\StarPvE\StarPvE;
use Lyrica0954\StarPvE\translate\DescriptionTranslator;
use Lyrica0954\StarPvE\utils\EntityUtil;
use Lyrica0954\StarPvE\utils\PlayerUtil;
use Lyrica0954\StarPvE\utils\TaskUtil;
use Lyrica0954\StarPvE\utils\VectorUtil;
use pocketmine\block\Block;
use pocketmine\block\BlockFactory;
use pocketmine\block\BlockLegacyIds;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Vector3;
use pocketmine\scheduler\ClosureTask;
use pocketmine\scheduler\Task;
use pocketmine\scheduler\TaskHandler;
use pocketmine\world\particle\BlockBreakParticle;

class QuakeAbility extends Ability {

    public function getCooltime(): int{
        return (9 * 20);
    }

    public function getName(): string{
        return "グラウンドインパクト";
    }

    public function getDescription(): string{
        $damage = DescriptionTranslator::health($this->damage);
        $area = DescriptionTranslator::number($this->area, "m");
        return 
sprintf('§b発動時:§f 視線の先に %1$s のダメージを与えて、敵を高く飛ばす横 %2$s 、縦 %2$s 、飛距離 §c7m§f の衝撃波を放つ。', $damage, $area);
    }

    protected function init(): void{
        $this->damage = new AbilityStatus(7.0);
        $this->area = new AbilityStatus(3.0);
    }

    protected function onActivate(): ActionResult{
        $world = $this->player->getWorld();

        #変更: MemoryEntity のaabbを変更してそれの衝突を調べる。-> 速度もあるからよりリアルっぽくなる
        $area = $this->area->get();
        foreach(EntityUtil::getLineOfSight($this->player, 7, new Vector3($area, $area, $area)) as $result){
            $entity = $result->getEntity();
            if (MonsterData::isMonster($entity)){
                (new SingleParticle())->sendToPlayers($world->getPlayers(), $entity->getPosition(), "starpve:quake");
                $source = new EntityDamageByEntityEvent($this->player, $entity, EntityDamageByEntityEvent::CAUSE_ENTITY_ATTACK, $this->damage->get());
                EntityUtil::attackEntity($source, 0.0, 2.5, true);
            }
        }

        TaskUtil::repeatingClosureLimit(function(){
            PlayerUtil::broadcastSound($this->player, "leashknot.break", 1.3);
        }, 1, 3);

        return ActionResult::SUCCEEDED();
    }
}