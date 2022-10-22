<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\form;

use Lyrica0954\StarPvE\job\player\PlayerJob;
use pocketmine\form\Form;
use pocketmine\player\Player;

class SelectSpellForm extends SpellListForm {

	public function __construct(
		protected PlayerJob $job,
		array $spells
	) {
		parent::__construct($spells);
	}

	public function jsonSerialize(): mixed {
		$parentData = parent::jsonSerialize();
		$parentData["title"] = "ショップ >> 職業 >> {$this->job->getName()} >> スペル選択";
		$parentData["buttons"][] = ["text" => "選択しない"];
		$parentData["content"] = "習得するスペルを選択してください";
		return $parentData;
	}

	public function handleResponse(Player $player, $data): void {
		parent::__handleResponse($player, $data);
		if ($data !== null) {
			$spell = $this->spells[$data] ?? null;
			if ($spell !== null) {
				$form = new SpellInformationSelectableForm($this->job, $spell);
				$form->setChildForm($this);
				$player->sendForm($form);
			}
		}
	}
}
