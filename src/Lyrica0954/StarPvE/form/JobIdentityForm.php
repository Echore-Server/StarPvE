<?php

declare(strict_types=1);


namespace Lyrica0954\StarPvE\form;

use Lyrica0954\StarPvE\form\help\HelpIdentityForm;
use Lyrica0954\StarPvE\identity\Identity;
use Lyrica0954\StarPvE\identity\player\PlayerArgIdentity;
use Lyrica0954\StarPvE\job\JobIdentity;
use Lyrica0954\StarPvE\job\player\PlayerJob;
use Lyrica0954\StarPvE\utils\Messanger;
use pocketmine\form\Form;
use pocketmine\player\Player;

class JobIdentityForm implements Form {

	private array $identities;

	public function __construct(private Player $player, private PlayerJob $job) {
	}

	public function jsonSerialize(): mixed {
		$identityGroup = $this->job->getIdentityGroup();

		$buttons = [];

		$buttons[] = ["text" => "特性とは？ §7(ヘルプに進みます)"];

		$activateableIdentity = [];
		$identityCount = count($identityGroup->getAll());
		foreach ($identityGroup->getAll() as $identity) {
			$activateable = $identity->isApplicable();

			if ($identity instanceof PlayerArgIdentity || $identity instanceof JobIdentity) {
				$activateable = $identity->isApplicableFor($this->player);
			}

			if ($activateable) {
				$activateableIdentity[] = $identity;
			}

			$desc = $activateable ? "§a有効" : "§c無効";
			$fixed = FormUtil::fixText($identity->getDescription());
			$buttons[] = [
				"text" => "§l§6{$identity->getName()}\n§r{$desc} §f/ §7{$fixed}"
			];
			$this->identities[] = $identity;
		}
		$activateableIdentityCount = count($activateableIdentity);

		return [
			"type" => "form",
			"title" => "ショップ >> 職業 >> {$this->job->getName()} >> 特性",
			"content" => "現在 §b{$activateableIdentityCount}/{$identityCount} §fの特性が有効です！",
			"buttons" => $buttons
		];
	}

	public function handleResponse(Player $player, $data): void {
		if ($data !== null) {
			if ($data == 0) {
				$form = new HelpIdentityForm();
				$player->sendForm($form);
			} else {
				$identity = $this->identities[$data - 1] ?? null;
				if ($identity !== null) {
					$player->sendMessage(Messanger::talk("職業", "§cこの特性を有効するには以下の条件を満たす必要があります"));
					Messanger::condition($player, $identity->getCondition());
				} else {
					Messanger::error($player, "Identity index error", Messanger::getIdFromObject($this, "handleResponse"));
				}
			}
		}
	}
}
