<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\job\player\engineer;

use Lyrica0954\StarPvE\job\Ability;
use Lyrica0954\StarPvE\job\AbilityStatus;
use Lyrica0954\StarPvE\job\ActionResult;
use Lyrica0954\StarPvE\job\player\engineer\entity\GravityBall;
use Lyrica0954\StarPvE\job\player\engineer\entity\ShieldBall;
use Lyrica0954\StarPvE\job\Skill;
use Lyrica0954\StarPvE\translate\DescriptionTranslator;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;

class ThrowShieldBallSkill extends Skill {

    public function getCooltime(): int{
        return (110 * 20);
    }

    public function getName(): string{
        return "シールドボール";
    }

    public function getDescription(): string{
        $lossPeriod = DescriptionTranslator::second($this->duration);
        # %%%% は sprintfで %% と認識されるが、クライアント側でまたフォーマットしてるらしく、結局 %単体になる。
        return
sprintf('§b発動時:§f 視線の先にシールドボールを射出する。ボールが地面についてしばらくすると効果が発動される。
§b効果:§f %1$s につき §c1%%%%§f の§eパワー§fを消費して、シールドを展開する。シールドの大きさは§eパワー§fによって変わる。§f
シールド内に敵が侵入した場合、§eパワー§fを少し消費して、敵をはじいてシールド内から追い出す。

はじいた敵が§dクリーパー§fの場合は、瞬時に爆発させる。爆発のダメージは変わらないが、シールド内にいる場合はダメージが無効化される。
また、シールド内に飛び道具が入った場合、§eパワー§fを消費してその飛び道具を無効化することができる。
パワーが§c0%%%%§fになると、効果が消失する。', $lossPeriod);
    }

    protected function init(): void{
        $this->duration = new AbilityStatus(1 * 20);
        $this->speed = new AbilityStatus(0.9);
    }

    protected function onActivate(): ActionResult{
        $item = ItemFactory::getInstance()->get(ItemIds::NETHER_REACTOR);
        $motion = $this->player->getDirectionVector()->multiply($this->speed->get());
        $loc = $this->player->getLocation();
        $loc->y += $this->player->getEyeHeight();
        $entity = new ShieldBall($loc, $item);
        $entity->lossPeriod = (integer) $this->duration->get();
        $entity->setMotion($motion);
        $entity->setOwningEntity($this->player);
        $entity->spawnToAll();
        
        return ActionResult::SUCCEEDED();
    }
}