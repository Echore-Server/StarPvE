<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\job\player\healer;

use Lyrica0954\MagicParticle\CircleParticle;
use Lyrica0954\MagicParticle\EmitterParticle;
use Lyrica0954\MagicParticle\LineParticle;
use Lyrica0954\MagicParticle\SingleParticle;
use Lyrica0954\StarPvE\entity\Villager;
use Lyrica0954\StarPvE\job\AbilityStatus;
use Lyrica0954\StarPvE\job\ActionResult;
use Lyrica0954\StarPvE\job\Skill;
use Lyrica0954\StarPvE\StarPvE;
use Lyrica0954\StarPvE\translate\DescriptionTranslator;
use Lyrica0954\StarPvE\utils\EffectGroup;
use Lyrica0954\StarPvE\utils\EntityUtil;
use Lyrica0954\StarPvE\utils\PlayerUtil;
use Lyrica0954\StarPvE\utils\VectorUtil;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\event\entity\EntityRegainHealthEvent;
use pocketmine\player\Player;

class ConcordeSkill extends Skill {

    /**
     * @var AbilityStatus
     */
    protected AbilityStatus $heal;

    protected EffectGroup $normalEffects;
    protected EffectGroup $fighterEffects;
    protected EffectGroup $villagerEffects;

	public function getCooltime(): int{
		return (140 * 20);
	}

    public function getName(): string{
        return "コンコルド";
    }

    public function getDescription(): String{
        $area = DescriptionTranslator::number($this->area, "m");
        $heal = DescriptionTranslator::health($this->heal);
        $fighter = DescriptionTranslator::job("Fighter");
        $normalEffects = DescriptionTranslator::effectGroup($this->normalEffects);
        $fighterEffects = DescriptionTranslator::effectGroup($this->fighterEffects);
        $villagerEffects = DescriptionTranslator::effectGroup($this->villagerEffects);
        return
sprintf('§b発動時(1):§f %1$s 以内の味方(自分以外)の体力を %2$s 回復させ、%3$s を与える。
もし回復させた味方の職業が %4$s の場合、追加で %5$s を与える。
さらに、自分自身を全回復させる。
§b発動時(2):§f %1$s 以内の村人に %6$s を与える。', $area, $heal, $normalEffects, $fighter, $fighterEffects, $villagerEffects);
    }

    protected function init(): void{
        $this->area = new AbilityStatus(12.0);
        $this->heal = new AbilityStatus(5 * 2);
        $this->normalEffects = new EffectGroup(
            new EffectInstance(VanillaEffects::ABSORPTION(), (30 * 20), 3),
            new EffectInstance(VanillaEffects::REGENERATION(), (20 * 20), 2),
            new EffectInstance(VanillaEffects::RESISTANCE(), (30 * 20), 0)
        );

        $this->fighterEffects = new EffectGroup(
            new EffectInstance(VanillaEffects::STRENGTH(), (18 * 20), 1),
            new EffectInstance(VanillaEffects::SPEED(), (18 * 20), 1)
        );

        $this->villagerEffects = new EffectGroup(
            new EffectInstance(VanillaEffects::RESISTANCE(), (14 * 20), 255, true)
        );
    }

    public function getHeal(): AbilityStatus{
        return $this->heal;
    }

    protected function onActivate(): ActionResult{

        $par = (new SingleParticle);
        $linePar = (new LineParticle(VectorUtil::keepAdd($this->player->getPosition(), 0, 0.5, 0), 3));
        $circlePar = (new CircleParticle($this->area->get(), $this->area->get()));
        $players = $this->player->getWorld()->getPlayers();
        foreach(EntityUtil::getWithinRange($this->player->getPosition(), $this->area->get()) as $entity){
            if ($entity instanceof Player){
                if ($entity !== $this->player){
                    $regain = new EntityRegainHealthEvent($entity, $this->heal->get(), EntityRegainHealthEvent::CAUSE_CUSTOM);
                    $entity->heal($regain);
                    $this->normalEffects->apply($entity);
                    $parPos = VectorUtil::keepAdd(
                        $entity->getPosition(),
                        0,
                        ($entity->getEyeHeight() + 0.5),
                        0
                    );
                    if (StarPvE::getInstance()->getJobManager()->isJobName($entity, "Fighter")){
                        $this->fighterEffects->apply($entity);
                        $par->sendToPlayers(
                            $players,
                            $parPos,
                            "minecraft:villager_angry"
                        );
                    } else {
                        $par->sendToPlayers(
                            $players,
                            $parPos,
                            "minecraft:heart_particle"
                        );
                    }

                    $linePar->sendToPlayers(
                        $players,
                        VectorUtil::keepAdd(
                            $entity->getPosition(),
                            0,
                            0.75,
                            0
                        ),
                        "minecraft:villager_happy"
                    );
                }

                PlayerUtil::playSound($entity, "random.orb");
                PlayerUtil::playSound($entity, "random.totem", 0.75, 0.5);
            } elseif ($entity instanceof Villager){
                $this->villagerEffects->apply($entity);
            }
        }

        $selfRegain = new EntityRegainHealthEvent($this->player, $this->player->getMaxHealth(), EntityRegainHealthEvent::CAUSE_CUSTOM);
        $this->player->heal($selfRegain);

        $circlePar->sendToPlayers(
            $players,
            VectorUtil::keepAdd($this->player->getPosition(), 0, 0.25, 0),
            "minecraft:falling_dust_sand_particle"
        );

        return ActionResult::SUCCEEDED();
    }
}