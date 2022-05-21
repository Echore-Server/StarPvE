<?php

declare(strict_types=1);


namespace Lyrica0954\StarPvE\form;

use Lyrica0954\StarPvE\identity\Identity;
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

		$activateableIdentity = [];
		$identityCount = count($identityGroup->getAll());
		foreach ($identityGroup->getAll() as $identity) {
			$activateable = $identity->isActivateableFor($this->player);
			$activateableIdentity[] = $identity;
			$desc = $activateable ? "§a有効" : "§c無効";
			$buttons[] = [
				"text" => "§l§6{$identity->getName()}\n§r{$desc} §f/ §7{$identity->getDescription()}"
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
			$identity = $this->identities[$data] ?? null;
			if ($identity instanceof Identity) {
				Messanger::talk($player, "職業", "§cこの特性を有効するには以下の条件を満たす必要があります");
				Messanger::condition($player, $identity->getActivateCondition());
			}
		}
	}
}
