<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\form\help;

use pocketmine\form\Form;
use pocketmine\player\Player;

class HelpForm implements Form {
	public function __construct() {
	}

	public function jsonSerialize(): mixed {
		return [
			"type" => "form",
			"title" => "ヘルプ >> 状態異常",
			"content" => "",
			"buttons" => [
				[
					"text" => "特性"
				],
				[
					"text" => "状態異常"
				]
			]
		];
	}

	public function handleResponse(Player $player, $data): void {
		if ($data !== null) {
			if ($data === 0) {
				$form = new HelpIdentityForm;
				$form->setChildForm($this);
				$player->sendForm($form);
			} elseif ($data === 1) {
				$form = new HelpStatesForm;
				$form->setChildForm($this);
				$player->sendForm($form);
			}
		}
	}
}
