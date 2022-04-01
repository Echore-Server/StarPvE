<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\job\player\engineer;

use Lyrica0954\StarPvE\job\Ability;
use Lyrica0954\StarPvE\job\AbilityStatus;
use Lyrica0954\StarPvE\job\ActionResult;
use Lyrica0954\StarPvE\job\player\engineer\entity\GravityBall;
use Lyrica0954\StarPvE\translate\DescriptionTranslator;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;

class ThrowGravityBallAbility extends Ability {


    protected EffectInstance $effect;

    public function getCooltime(): int{
        return (10 * 20);
    }

    public function getName(): string{
        return "グラビティボール";
    }

    public function getDescription(): string{
        $area = DescriptionTranslator::number($this->area, "m");
        $amount = DescriptionTranslator::number($this->amount, "回");
        $effect = DescriptionTranslator::effect($this->effect);
        $duration = DescriptionTranslator::second($this->duration);
        return 
sprintf('§b発動時:§f 視線の先にグラビティボールを射出する。地面について少しすると、効果が発動される。
§b効果:§f グラビティボールから %1$s 以内の敵を §c1秒§f に %2$s 引き寄せる。引き寄せる際に %3$s のエフェクトを与える。
§dハスク§fに対しては引き寄せが無効化される。
効果を発動してから %4$s 経過すると、効果が消失する。', $area, $amount, $effect, $duration);
    }

    protected function init(): void{
        $this->speed = new AbilityStatus(0.9);
        $this->area = new AbilityStatus(5.0);
        $this->amount = new AbilityStatus(2.0);
        $this->duration = new AbilityStatus(11 * 20);
        $this->effect = new EffectInstance(VanillaEffects::SLOWNESS(), 30, 1, false, true);
    }

    protected function onActivate(): ActionResult{
        $item = ItemFactory::getInstance()->get(ItemIds::FIRE_CHARGE);
        $motion = $this->player->getDirectionVector()->multiply($this->speed->get());
        $loc = $this->player->getLocation();
        $loc->y += $this->player->getEyeHeight();
        $entity = new GravityBall($loc, $item);
        $entity->limit = $this->duration->get();
        $entity->area = $this->area->get();
        $entity->period = (integer) floor(20 / $this->amount->get());
        $entity->effect = clone $this->effect;
        $entity->setMotion($motion);
        $entity->setOwningEntity($this->player);
        $entity->spawnToAll();
        
        return ActionResult::SUCCEEDED();
    }
}