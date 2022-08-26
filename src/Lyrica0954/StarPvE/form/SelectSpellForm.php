<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\form;

use Lyrica0954\StarPvE\job\player\PlayerJob;
use pocketmine\form\Form;
use pocketmine\player\Player;

class SelectSpellForm extends SpellListForm {

	public function __construct(
		protected PlayerJob $job,
	) {
		parent::__construct($job->getDefaultSpells());
	}

	public function jsonSerialize(): mixed {
		$parentData = parent::jsonSerialize();
		$parentData["title"] = "ショップ >> 職業 >> {$this->job->getName()} >> スペル選択";
		return $parentData;
	}

	public function handleResponse(Player $player, $data): void {
		if ($data !== null) {
			$spell = $this->spells[$data] ?? null;
			if ($spell !== null) {
				$form = new SpellInformationSelectableForm($this->job, $spell);
				$player->sendForm($form);
			}
		}
	}
}
