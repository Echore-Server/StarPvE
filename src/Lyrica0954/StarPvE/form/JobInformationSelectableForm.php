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
		array_unshift($parentData["buttons"], ["text" => "§a§lこの職業に就く"]);
		$parentData["buttons"][] = ["text" => "戻る"];
		return $parentData;
	}

	public function handleResponse(Player $player, $data): void {
		if ($player === $this->player) {
			if ($data !== null) {
				if ($data == 0) {
					if ($this->job->isSelectable($player)) {
						$class = $this->job::class;
						StarPvE::getInstance()->getJobManager()->setJob($player, $class);
						$player->sendMessage(Messanger::talk("職業", "§a{$this->job->getName()} を選択しました！"));
						$jobInstance = StarPvE::getInstance()->getJobManager()->getJob($player);
						if (count($jobInstance->getDefaultSpells()) > 0) {
							TaskUtil::delayed(new ClosureTask(function () use ($player, $jobInstance) {
								$form = new SelectSpellForm($jobInstance);
								$player->sendForm($form);
							}), 1);
						}
					} else {
						$player->sendMessage(Messanger::talk("職業", "§c{$this->job->getName()} を選択できません"));
					}
				} elseif ($data == 1) {
					TaskUtil::delayed(new ClosureTask(function () use ($player) {
						$jobIdentity = new JobIdentityForm($player, $this->job);
						$player->sendForm($jobIdentity);
					}), 1);
				} elseif ($data == 2) {
					TaskUtil::delayed(new ClosureTask(function () use ($player) {
						$jobIdentity = new SpellListForm($this->job->getSpells());
						$player->sendForm($jobIdentity);
					}), 1);
				} else {
					TaskUtil::delayed(new ClosureTask(function () use ($player) {
						$jobSelect = new JobSelectForm($player);
						$player->sendForm($jobSelect);
					}), 1);
				}
			}
		} else {
			Messanger::error($player, "Invalid Sender", Messanger::getIdFromObject($this, "handleResponse"));
		}
	}
}
