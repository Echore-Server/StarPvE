<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\form;

use Closure;
use Lyrica0954\StarPvE\game\Game;
use Lyrica0954\StarPvE\job\Job;
use Lyrica0954\StarPvE\StarPvE;
use Lyrica0954\StarPvE\utils\Messanger;
use pocketmine\form\Form;
use pocketmine\player\Player;
use pocketmine\scheduler\ClosureTask;

class JobSelectForm extends AdvancedForm {

	private array $jobs;

	public function __construct(private Player $player) {
		$this->jobs = StarPvE::getInstance()->getJobManager()->getRegisteredJobs();
	}

	public function jsonSerialize(): mixed {
		$buttons = [];
		foreach ($this->jobs as $jobClass) {
			$job = new $jobClass(null);
			if ($job instanceof Job) {
				$color = $job->isSelectable($this->player) ? "§a" : "§c§k";
				$buttons[] = [
					"text" => "{$color}{$job->getName()}"
				];
			}
		}
		return [
			"type" => "form",
			"title" => "ショップ >> 職業 >> 職業一覧",
			"content" => "",
			"buttons" => $buttons
		];
	}

	public function handleResponse(Player $player, $data): void {
		parent::handleResponse($player, $data);
		if ($data !== null) {
			if (($jobClass = (array_values($this->jobs)[$data] ?? null)) !== null) {
				$job = new $jobClass(null);
				if ($job instanceof Job) {
					if ($job->isSelectable($player)) {
						$jobInformation = new JobInformationSelectableForm($player, $job);
						$jobInformation->setChildForm($this);
						$player->sendForm($jobInformation);
					} else {
						$player->sendMessage(Messanger::talk("職業", "§cこの職業を選択するには以下の条件を満たす必要があります"));
						Messanger::condition($player, $job->getSelectableCondition());
					}
				}
			}
		}
	}
}
