<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\form;

use pocketmine\form\Form;
use pocketmine\player\Player;

abstract class AdvancedForm implements Form {
	use ChildFormTrait;

	public function handleResponse(Player $player, $data): void {
		$this->__handleResponse($player, $data);
	}
}
