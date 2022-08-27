<?php


declare(strict_types=1);

namespace Lyrica0954\StarPvE\form;

use Lyrica0954\StarPvE\job\player\PlayerJob;
use Lyrica0954\StarPvE\job\Spell;
use Lyrica0954\StarPvE\StarPvE;
use pocketmine\player\Player;

class SpellInformationSelectableForm extends SpellInformationForm {

	public function __construct(protected PlayerJob $job, Spell $spell) {
		parent::__construct($spell);
	}

	public function jsonSerialize(): mixed {
		$parentData = parent::jsonSerialize();
		$parentData["buttons"][] = ["text" => "§a習得する"];
		$parentData["buttons"][] = ["text" => "戻る"];
		$parentData["title"] = "ショップ >> 職業 >> {$this->job->getName()} >> スペルリスト >> {$this->spell->getName()}";
		return $parentData;
	}

	public function handleResponse(Player $player, $data): void {
		if ($data !== null) {
			if ($data == 0) {
				$job = StarPvE::getInstance()->getJobManager()->getJob($player);
				if ($job instanceof PlayerJob) {
					$job->addSpell(clone $this->spell);
				} else {
					$player->sendMessage("§cあなたは現在職業についていません！");
				}
			} elseif ($data == 1) {
				$form = new SelectSpellForm($this->job);
				$player->sendForm($form);
			}
		}
	}
}
