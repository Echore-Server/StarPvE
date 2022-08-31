<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\form;

use pocketmine\form\Form;
use pocketmine\player\Player;

trait ChildFormTrait {

	protected ?Form $child = null;

	public function getChildForm(): ?Form {
		return $this->child;
	}

	public function setChildForm(Form $form): void {
		$this->child = $form;
	}

	public function __handleResponse(Player $player, $data) {
		if ($data === null) {
			if ($this->child !== null) {
				$player->sendForm($this->child);
			}
		}
	}
}
