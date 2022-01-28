<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\job\player\magician;

use Lyrica0954\MagicParticle\SphereParticle;
use Lyrica0954\StarPvE\game\wave\MonsterData;
use Lyrica0954\StarPvE\job\Ability;
use Lyrica0954\StarPvE\job\ActionResult;
use Lyrica0954\StarPvE\job\Skill;
use Lyrica0954\StarPvE\job\ticking\Ticking;
use Lyrica0954\StarPvE\job\ticking\TickingController;
use Lyrica0954\StarPvE\StarPvE;
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

    public function getCooltime(): int{
        return (80 * 20);
    }

    protected function onActivate(): ActionResult{
        PlayerUtil::playSound($this->player, "firework.launch");
        $this->player->getEffects()->add(new EffectInstance(VanillaEffects::SPEED(), 12 * 20, 1));
        $this->active = true;
        StarPvE::getInstance()->getScheduler()->scheduleDelayedTask(new ClosureTask(function (){
            PlayerUtil::playSound($this->player, "random.fizz", 0.5);
            $this->active = false;
        }), (12 * 20));

        return ActionResult::SUCCEEDED();
    }
}