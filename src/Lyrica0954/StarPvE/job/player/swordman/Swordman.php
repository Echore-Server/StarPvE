<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\job\player\swordman;

use Lyrica0954\StarPvE\data\condition\Condition;
use Lyrica0954\StarPvE\job\Ability;
use Lyrica0954\StarPvE\job\player\PlayerJob;
use Lyrica0954\StarPvE\job\Skill;
use ParentIterator;
use pocketmine\network\mcpe\protocol\types\ParticleIds;



class Swordman extends PlayerJob{

    protected function getInitialAbility(): Ability{
        return new LeapAbility($this);
    }

    protected function getInitialSkill(): Skill{
        return new ForceFieldSkill($this);
    }

    public function getName(): string{
        return "Swordman";
    }

    public function getDescription(): string{
        return 
"§7- §l§c戦闘§r

俊敏に動けるソードマン。移動や、敵の吹き飛ばしなど、先陣を突っ切っていくのが得意な職業。
この職業はどの能力もクールタイムが短いため、どんどん使っていこう。";
    }

    public function getAbilityName(): string{
        return "リープ";
    }

    public function getAbilityDescription(): String{
        return 
"発動時: 視線の先に向かってジャンプする。
着地中: ジャンプの高さが低く飛距離が長い
空中: ジャンプの高さが高く飛距離は少し短い

ジャンプ中、周りに風を発生させ§c3.5m§f以内の敵全てを前方に吹き飛ばし、§c1.5♡§fのダメージを与える。
ジャンプ中、ノックバックしなくなる。
吹き飛ばした敵が§dアタッカー§fの場合は、吹き飛びが軽減される。";
    }

    public function getSkillName(): String{
        return "フォースフィールド";
    }

    public function getSkillDescription(): String{
        return
"発動時: §c9m§f以内の敵に§c3♡§fのダメージを与えて、遠くに吹き飛ばす。";
    }

    public function getSelectableCondition(): ?Condition{
        return null;
    }

}