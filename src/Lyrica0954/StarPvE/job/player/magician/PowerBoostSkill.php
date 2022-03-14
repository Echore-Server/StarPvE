<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\job\player\magician;

use Lyrica0954\MagicParticle\SphereParticle;
use Lyrica0954\StarPvE\game\wave\MonsterData;
use Lyrica0954\StarPvE\job\Ability;
use Lyrica0954\StarPvE\job\AbilityStatus;
use Lyrica0954\StarPvE\job\ActionResult;
use Lyrica0954\StarPvE\job\Skill;
use Lyrica0954\StarPvE\job\ticking\Ticking;
use Lyrica0954\StarPvE\job\ticking\TickingController;
use Lyrica0954\StarPvE\StarPvE;
use Lyrica0954\StarPvE\translate\DescriptionTranslator;
use Lyrica0954\StarPvE\utils\EntityUtil;
use Lyrica0954\StarPvE\utils\PlayerUtil;
use Lyrica0954\StarPvE\utils\VectorUtil;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\entity\Entity;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\network\mcpe\protocol\LevelEventPacket;
use pocketmine\network\mcpe\protocol\types\ActorEvent;
use pocketmine\network\mcpe\protocol\types\ParticleIds;
use pocketmine\scheduler\ClosureTask;
use pocketmine\world\particle\ExplodeParticle;

class PowerBoostSkill extends Skill{
    
    protected EffectInstance $effect;

    public function getCooltime(): int{
        return (80 * 20);
    }

    public function getName(): String{
        return "パワーブースト";
    }

    public function getDescription(): String{
        $duration = DescriptionTranslator::second($this->duration);
        $effect = DescriptionTranslator::effect($this->effect);
        return
sprintf('§b効果時間:§f %1$s
§b発動時:§f 花火が発射される音とともに、%2$s が付与される。
さらに発動中はアビリティのクールタイムが §c0.35秒§f にスピードアップする。', $duration, $effect);
    }

    protected function init(): void{
        $this->duration = new AbilityStatus(12 * 20);
        $this->effect = new EffectInstance(VanillaEffects::SPEED(), 12 * 20, 1);
    }

    protected function onActivate(): ActionResult{
        PlayerUtil::playSound($this->player, "firework.launch");
        $this->player->getEffects()->add(clone $this->effect);
        $this->active = true;
        StarPvE::getInstance()->getScheduler()->scheduleDelayedTask(new ClosureTask(function (){
            PlayerUtil::playSound($this->player, "random.fizz", 0.5);
            $this->active = false;
        }), (integer) $this->duration->get());

        return ActionResult::SUCCEEDED();
    }
}