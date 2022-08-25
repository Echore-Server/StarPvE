<?php

declare(strict_types=1);


namespace Lyrica0954\StarPvE\form;

use Lyrica0954\StarPvE\game\player\GamePlayer;
use Lyrica0954\StarPvE\identity\Identity;
use Lyrica0954\StarPvE\identity\player\AddMaxHealthArgIdentity;
use Lyrica0954\StarPvE\identity\player\AttackPercentageArgIdentity;
use Lyrica0954\StarPvE\identity\player\PlayerArgIdentity;
use Lyrica0954\StarPvE\identity\player\ReducePercentageArgIdentity;
use Lyrica0954\StarPvE\job\identity\ability\AttachAbilityIdentityBase as AAIB;
use Lyrica0954\StarPvE\job\identity\ability\IncreaseStatusIdentity;
use Lyrica0954\StarPvE\job\identity\ability\PercentageStatusIdentity;
use Lyrica0954\StarPvE\job\JobIdentity;
use Lyrica0954\StarPvE\job\player\PlayerJob;
use Lyrica0954\StarPvE\job\StatusTranslate;
use Lyrica0954\StarPvE\StarPvE;
use Lyrica0954\StarPvE\utils\Messanger;
use pocketmine\form\Form;
use pocketmine\player\Player;

class PerkIdentitiesForm implements Form {

	public static function generateIdentities(GamePlayer $gamePlayer): array {
		$playerJob = StarPvE::getInstance()->getJobManager()->getJob($gamePlayer->getPlayer());
		$identities = [
			new AttackPercentageArgIdentity(null, 0.08),
			new ReducePercentageArgIdentity(null, 0.06),
			new AddMaxHealthArgIdentity(null, 6)
		];

		if ($playerJob instanceof PlayerJob) {
			$rand = [
				new IncreaseStatusIdentity($playerJob, null, AAIB::ATTACH_ABILITY, StatusTranslate::STATUS_AMOUNT, 2),
				new PercentageStatusIdentity($playerJob, null, AAIB::ATTACH_ABILITY, StatusTranslate::STATUS_AREA, 1.34),
				new PercentageStatusIdentity($playerJob, null, AAIB::ATTACH_ABILITY, StatusTranslate::STATUS_DAMAGE, 1.26),
				new PercentageStatusIdentity($playerJob, null, AAIB::ATTACH_ABILITY, StatusTranslate::STATUS_DURATION, 1.34),
				new PercentageStatusIdentity($playerJob, null, AAIB::ATTACH_ABILITY, StatusTranslate::STATUS_PERCENTAGE, 1.24),
				new PercentageStatusIdentity($playerJob, null, AAIB::ATTACH_ABILITY, StatusTranslate::STATUS_SPEED, 1.4)
			];

			for ($i = 0; $i < 4; $i++) {
				$ind = array_rand($rand);
				$identity = clone $rand[$ind];
				if ($identity instanceof AAIB) {
					$l = [
						AAIB::ATTACH_ABILITY,
						AAIB::ATTACH_SKILL
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

	public function __construct(private GamePlayer $gamePlayer, private array $identities, private bool $internal = true) {
	}

	public function jsonSerialize(): mixed {

		$buttons = [];

		foreach ($this->identities as $identity) {
			$fixed = str_replace("%", "%%", $identity->getDescription());
			$compatibility = true;

			if ($identity instanceof AAIB) {
				$compatibility = $identity->isAppicableForAbility($identity->getAttaching());
			}

			$color = $compatibility ? "§a" : "§c";
			$buttons[] = [
				"text" => "§l{$color}{$identity->getName()}\n§r§7{$fixed}"
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
				$compatibility = true;
				if ($identity instanceof AAIB) {
					$compatibility = $identity->isAppicableForAbility($identity->getAttaching());
				}

				if (!$compatibility) {
					Messanger::talk($player, "特性", "§7この特性は取得しても効果がありません！");
					return;
				}

				$fn = function (Player $player, Identity $identity): void {
					$ig = $this->gamePlayer->getIdentityGroup();
					$ig->reset();
					$ci = clone $identity;
					if ($ci instanceof PlayerArgIdentity) {
						$ci->setPlayer($this->gamePlayer->getPlayer());
					}
					$ig->add($ci);
					$ig->apply();

					if (!$this->internal) {
						$this->gamePlayer->setPerkAvailable($this->gamePlayer->getPerkAvailable() - 1);
						$this->gamePlayer->rollPerkIdentities();
					}
					Messanger::talk($player, "特性", "§d{$identity->getName()} §7を習得しました！");
				};

				if ($identity instanceof AAIB) {
					$ability = clone $identity->getAttaching();

					$identity->applyAbility($ability);
					$content = "選択すると、アビリティが以下になります。\n\n{$ability->getDescription()}";

					$form = new YesNoForm($content, function (Player $player, $data) use ($fn, $identity): void {
						if ($data !== null) {
							if ($data === 0) {
								($fn)($player, $identity);
							}
						}
					});

					$player->sendForm($form);
				} else {
					$fn($player, $identity);
				}
			} else {
				Messanger::error($player, "Invalid Key", Messanger::getIdFromObject($this, "handleResponse"));
			}
		}
	}
}
