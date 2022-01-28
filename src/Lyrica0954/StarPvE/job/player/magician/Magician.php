<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\job\player\magician;

use Lyrica0954\StarPvE\data\condition\Condition;
use Lyrica0954\StarPvE\data\condition\LevelCondition;
use Lyrica0954\StarPvE\job\Ability;
use Lyrica0954\StarPvE\job\player\PlayerJob;
use Lyrica0954\StarPvE\job\Skill;



class Magician extends PlayerJob{

    protected function getInitialAbility(): Ability{
        return new ThunderboltAbility($this);
    }

    protected function getInitialSkill(): Skill{
        return new PowerBoostSkill($this);
    }

    public function getName(): string{
        return "Magician";
    }

    public function getDescription(): string{
        return 
"§7- §l§c戦闘§r

雷を操るマジシャン。遠距離から敵を攻撃したり、集団の敵を殲滅するのが得意な職業。
全ての職業の中でもかなり秒間攻撃力が高いこの職業だが、
アビリティやスキルなどで、敵をノックバックさせることができないため
敵に狙われると少し危ない。";
    }

    public function getAbilityName(): string{
        return "サンダーボルト";
    }

    public function getAbilityDescription(): String{
        return 
"発動時: 視線の先に§e稲妻§fを放つ。
稲妻が敵に当たった場合、その敵に§c3.5♡§fのダメージを与えて、その敵の§c8.5m§f以内に別の敵がいた場合は、その敵にも§e稲妻§fが回っていく(チェイン)。
チェインによって与えられるダメージは§c2♡§fで、最大§c6回§fまでチェインできる。。";
    }

    public function getSkillName(): String{
        return "パワーブースト";
    }

    public function getSkillDescription(): String{
        return
"§b効果時間: §c12秒§f
発動時: 花火が発射される音とともに、§aスピードII§fが付与される。
さらに発動中はアビリティのクールタイムが§c0.35秒§fにスピードアップする。";
    }

    public function getSelectableCondition(): ?Condition{
        return new LevelCondition(2);
    }

}