<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\form;

use Lyrica0954\StarPvE\job\Ability;
use Lyrica0954\StarPvE\job\AbilitySpell;
use Lyrica0954\StarPvE\job\IdentitySpell;
use Lyrica0954\StarPvE\job\Spell;
use pocketmine\form\Form;
use pocketmine\player\Player;

class SpellListForm extends AdvancedForm {

	public function __construct(
		protected array $spells,
	) {
	}

	public function jsonSerialize(): mixed {
		$buttons = [];
		foreach ($this->spells as $spell) {
			if ($spell instanceof IdentitySpell && !$spell->isApplicable()) {
				continue;
			}

			$buttons[] = [
				"text" => "§l§b{$spell->getName()}\n§r§8クリックで詳細を表示"
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
		parent::handleResponse($player, $data);
		if ($data !== null) {
			$spell = $this->spells[$data] ?? null;
			if ($spell !== null) {
				$form = new SpellInformationForm($spell);
				$form->setChildForm($this);
				$player->sendForm($form);
			}
		}
	}
}
