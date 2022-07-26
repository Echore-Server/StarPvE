<?php

declare(strict_types=1);


namespace Lyrica0954\StarPvE\form;

use Lyrica0954\StarPvE\game\player\GamePlayer;
use Lyrica0954\StarPvE\identity\Identity;
use Lyrica0954\StarPvE\identity\player\AddAttackDamageArgIdentity;
use Lyrica0954\StarPvE\identity\player\AddMaxHealthArgIdentity;
use Lyrica0954\StarPvE\identity\player\AttackPercentageArgIdentity;
use Lyrica0954\StarPvE\identity\player\PlayerArgIdentity;
use Lyrica0954\StarPvE\identity\player\ReducePercentageArgIdentity;
use Lyrica0954\StarPvE\job\identity\ability\AttachAbilityIdentityBase;
use Lyrica0954\StarPvE\job\identity\ability\IncreaseAreaIdentity;
use Lyrica0954\StarPvE\job\identity\ability\IncreaseDamageIdentity;
use Lyrica0954\StarPvE\job\identity\ability\IncreaseDurationIdentity;
use Lyrica0954\StarPvE\job\identity\ability\IncreasePercentageIdentity;
use Lyrica0954\StarPvE\job\JobIdentity;
use Lyrica0954\StarPvE\job\player\PlayerJob;
use Lyrica0954\StarPvE\StarPvE;
use Lyrica0954\StarPvE\utils\Messanger;
use pocketmine\form\Form;
use pocketmine\player\Player;

class PerkIdentitiesForm implements Form {

    public static function generateIdentities(GamePlayer $gamePlayer, int $wave): array {
        $playerJob = StarPvE::getInstance()->getJobManager()->getJob($gamePlayer->getPlayer());
        $identities = [
            new AttackPercentageArgIdentity(null, 0.1),
            new ReducePercentageArgIdentity(null, 0.1),
            new AddMaxHealthArgIdentity(null, 4)
        ];

        if ($playerJob instanceof PlayerJob) {
            $rand = [
                new IncreaseDamageIdentity($playerJob, null, AttachAbilityIdentityBase::ATTACH_ABILITY, 2.5),
                new IncreaseAreaIdentity($playerJob, null, AttachAbilityIdentityBase::ATTACH_ABILITY, 2.5),
                new IncreaseDurationIdentity($playerJob, null, AttachAbilityIdentityBase::ATTACH_ABILITY, 100),
                new IncreasePercentageIdentity($playerJob, null, AttachAbilityIdentityBase::ATTACH_ABILITY, 0.2),
                new IncreaseDamageIdentity($playerJob, null, AttachAbilityIdentityBase::ATTACH_SKILL, 5.5),
                new IncreaseAreaIdentity($playerJob, null, AttachAbilityIdentityBase::ATTACH_SKILL, 5.0),
                new IncreaseDurationIdentity($playerJob, null, AttachAbilityIdentityBase::ATTACH_SKILL, 200),
                new IncreasePercentageIdentity($playerJob, null, AttachAbilityIdentityBase::ATTACH_ABILITY, 0.2)
            ];

            for ($i = 0; $i <= 1; $i++) {
                $ind = array_rand($rand);
                $identity = clone $rand[$ind];
                if ($identity instanceof AttachAbilityIdentityBase) {
                    $l = [
                        AttachAbilityIdentityBase::ATTACH_ABILITY,
                        AttachAbilityIdentityBase::ATTACH_SKILL
                    ];

                    $attachTo = $l[array_rand($l)];

                    $identity->setAttach($attachTo);
                }
                $identities[] = $identity;
                unset($rand[$ind]);
            }
        }

        return $identities;
    }

    public function __construct(private GamePlayer $gamePlayer, private array $identities) {
    }

    public function jsonSerialize(): mixed {

        $buttons = [];

        foreach ($this->identities as $identity) {
            $fixed = str_replace("%", "%%", $identity->getDescription());
            $buttons[] = [
                "text" => "§l§6{$identity->getName()}\n§r§7{$fixed}"
            ];
            $this->identities[] = $identity;
        }

        return [
            "type" => "form",
            "title" => "パークリスト",
            "content" => "習得するパークを選択してください",
            "buttons" => $buttons
        ];
    }

    public function handleResponse(Player $player, $data): void {
        if ($data !== null) {
            $identity = $this->identities[$data] ?? null;
            if ($identity instanceof Identity) {
                $ig = $this->gamePlayer->getIdentityGroup();
                $ig->reset();
                $ci = clone $identity;
                if ($ci instanceof PlayerArgIdentity) {
                    $ci->setPlayer($this->gamePlayer->getPlayer());
                }
                $ig->add($ci);
                $ig->apply();
                Messanger::talk($player, "特性", "§d{$identity->getName()} §7を習得しました！");
            } else {
                Messanger::error($player, "Invalid Key", Messanger::getIdFromObject($this, "handleResponse"));
            }
        }
    }
}
