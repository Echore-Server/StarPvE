<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\form;

use Lyrica0954\StarPvE\event\job\player\PlayerSelectJobEvent;
use Lyrica0954\StarPvE\game\Game;
use Lyrica0954\StarPvE\job\AlwaysAbility;
use Lyrica0954\StarPvE\job\Job;
use Lyrica0954\StarPvE\job\player\PlayerJob;
use Lyrica0954\StarPvE\StarPvE;
use Lyrica0954\StarPvE\utils\Messanger;
use Lyrica0954\StarPvE\utils\TaskUtil;
use pocketmine\form\Form;
use pocketmine\player\Player;
use pocketmine\scheduler\ClosureTask;

class JobInformationSelectableForm extends JobInformationForm {

	public function jsonSerialize(): mixed {
		$parentData = parent::jsonSerialize();
		$parentData["buttons"][] = ["text" => "§a§lこの職業に就く"];
		return $parentData;
	}

	public function handleResponse(Player $player, $data): void {
		parent::handleResponse($player, $data);
		if ($player === $this->player) {
			if ($data !== null) {
				if ($data == 3) {
					if ($this->job->isSelectable($player)) {
						$class = $this->job::class;
						StarPvE::getInstance()->getJobManager()->setJob($player, $class);
						$player->sendMessage(Messanger::talk("職業", "§a{$this->job->getName()} を選択しました！"));
						$jobInstance = StarPvE::getInstance()->getJobManager()->getJob($player);
						if (count($jobInstance->getDefaultSpells()) > 0) {
							$form = new SelectSpellForm($jobInstance, $jobInstance->getDefaultSpells());
							$form->setChildForm($form); #loop
							$player->sendForm($form);
						}
					} else {
						$player->sendMessage(Messanger::talk("職業", "§c{$this->job->getName()} を選択できません"));
					}
				}
			}
		}
	}
}
