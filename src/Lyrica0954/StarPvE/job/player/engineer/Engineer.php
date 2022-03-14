<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\job\player\engineer;

use Lyrica0954\StarPvE\data\condition\Condition;
use Lyrica0954\StarPvE\job\Ability;
use Lyrica0954\StarPvE\job\IdentityGroup;
use Lyrica0954\StarPvE\job\player\PlayerJob;
use Lyrica0954\StarPvE\job\Skill;

class Engineer extends PlayerJob {

    protected function getInitialIdentityGroup(): IdentityGroup{
        return new IdentityGroup($this);
    }

    protected function getInitialAbility(): Ability{
        return new ThrowGravityBallAbility($this);
    }

    protected function getInitialSkill(): Skill{
        return new ThrowShieldBallSkill($this);
    }

    public function getName(): string{
        return "Engineer";
    }

    public function getDescription(): string{
        return 
"§7- §l§a支援[⚔]§r

特殊なアビリティーを持つエンジニア。
シールドで味方を守ったり、敵の進行を止めたりできる優秀な職業だが、
どのアビリティでもダメージを与えることができないため、敵の殲滅にはあまり向いていない。";
    }

    public function getSelectableCondition(): ?Condition{
        return null;
    }
}