<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\job\player\healer;

use Lyrica0954\MagicParticle\CircleParticle;
use Lyrica0954\MagicParticle\LineParticle;
use Lyrica0954\MagicParticle\ParticleOption;
use Lyrica0954\MagicParticle\SingleParticle;
use Lyrica0954\StarPvE\job\Ability;
use Lyrica0954\StarPvE\job\AbilityStatus;
use Lyrica0954\StarPvE\job\ActionResult;
use Lyrica0954\StarPvE\StarPvE;
use Lyrica0954\StarPvE\translate\DescriptionTranslator;
use Lyrica0954\StarPvE\utils\EffectGroup;
use Lyrica0954\StarPvE\utils\EntityUtil;
use Lyrica0954\StarPvE\utils\ParticleUtil;
use Lyrica0954\StarPvE\utils\PlayerUtil;
use Lyrica0954\StarPvE\utils\VectorUtil;
use pocketmine\entity\effect\Effect;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\player\Player;

class HarmonyAbility extends Ability {

    protected EffectGroup $normalEffects;
    protected EffectGroup $fighterEffects;

    public function getCooltime(): int {
        return (25 * 20);
    }

    public function getName(): string {
        return "ハーモニー";
    }

    public function getDescription(): string {
        $area = DescriptionTranslator::number($this->area, "m");
        $fighter = DescriptionTranslator::job("Fighter");
        $normalEffects = DescriptionTranslator::effectGroup($this->normalEffects);
        $fighterEffects = DescriptionTranslator::effectGroup($this->fighterEffects);
        return
            sprintf('§b発動時:§f %1$s 以内の味方(自分以外)に %2$s を与える。
もし回復させた味方が %3$s の場合、追加で %4$s を与える。', $area, $normalEffects, $fighter, $fighterEffects);
    }

    protected function init(): void {
        $this->area = new AbilityStatus(12.0);
        $this->normalEffects = new EffectGroup(
            new EffectInstance(VanillaEffects::ABSORPTION(), (12 * 20), 0),
            new EffectInstance(VanillaEffects::REGENERATION(), (6 * 20), 2)
        );
        $this->fighterEffects = new EffectGroup(
            new EffectInstance(VanillaEffects::STRENGTH(), (9 * 20), 0)
        );
    }

    protected function onActivate(): ActionResult {

        $par = (new SingleParticle);
        $linePar = (new LineParticle(VectorUtil::keepAdd($this->player->getPosition(), 0, 0.5, 0), 3));
        $circlePar = (new CircleParticle($this->area->get(), 6));
        $players = $this->player->getWorld()->getPlayers();
        foreach (EntityUtil::getWithinRange($this->player->getPosition(), $this->area->get()) as $entity) {
            if ($entity instanceof Player) {
                if ($entity !== $this->player) {
                    $this->normalEffects->apply($entity);
                    $parPos = VectorUtil::keepAdd(
                        $entity->getPosition(),
                        0,
                        ($entity->getEyeHeight() + 0.5),
                        0
                    );
                    if (StarPvE::getInstance()->getJobManager()->isJobName($entity, "Fighter")) {
                        $this->fighterEffects->apply($entity);
                        ParticleUtil::send(
                            $par,
                            $players,
                            $parPos,
                            ParticleOption::spawnPacket("minecraft:villager_angry", "")
                        );
                    } else {
                        ParticleUtil::send(
                            $par,
                            $players,
                            $parPos,
                            ParticleOption::spawnPacket("minecraft:heart_particle", "")
                        );
                    }

                    ParticleUtil::send(
                        $linePar,
                        $players,
                        VectorUtil::keepAdd(
                            $entity->getPosition(),
                            0,
                            0.75,
                            0
                        ),
                        ParticleOption::spawnPacket("minecraft:villager_happy", "")
                    );
                }

                PlayerUtil::playSound($entity, "random.orb");
                PlayerUtil::playSound($entity, "random.glass", 1.5, 0.75);
            }
        }

        ParticleUtil::send(
            $circlePar,
            $players,
            VectorUtil::keepAdd($this->player->getPosition(), 0, 0.25, 0),
            ParticleOption::spawnPacket("minecraft:falling_dust_sand_particle", "")
        );

        return ActionResult::SUCCEEDED();
    }
}
