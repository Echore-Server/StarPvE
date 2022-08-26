<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\form;

use Lyrica0954\StarPvE\job\Ability;
use Lyrica0954\StarPvE\job\AbilitySpell;
use Lyrica0954\StarPvE\job\IdentitySpell;
use Lyrica0954\StarPvE\job\Spell;
use pocketmine\form\Form;
use pocketmine\player\Player;

class SpellListForm implements Form {

	public function __construct(
		protected array $spells,
	) {
	}

	public function jsonSerialize(): mixed {
		$buttons = [];
		foreach ($this->spells as $spell) {
			$buttons[] = [
				"text" => "§l§b{$spell->getName()}"
			];
		}

		return [
			"type" => "form",
			"title" => "スペルリスト",
			"content" => "",
			"buttons" => $buttons
		];
	}

	public function handleResponse(Player $player, $data): void {
		if ($data !== null) {
			$spell = $this->spells[$data] ?? null;
			if ($spell !== null) {
				$form = new SpellInformationForm($spell);
				$player->sendForm($form);
			}
		}
	}
}
