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

class JobInformationForm extends AdvancedForm {

	public function __construct(protected Player $player, protected PlayerJob $job) {
	}

	public function jsonSerialize(): mixed {
		if ($this->job instanceof AlwaysAbility) {
			#????
			$add = "§l常時アビリティ§r §7- §d{$this->job->getAlAbilityName()}§f\n{$this->job->getAlAbilityDescription()}\n§f---------------------------\n";
		} else {
			$add = "";
		}

		$abilityCooltime = round($this->job->getAbility()->getCooltime()->get() / 20, 1);
		$skillCooltime = round($this->job->getSkill()->getCooltime()->get() / 20, 1);
		return [
			"type" => "form",
			"title" => "ショップ >> 職業 >> {$this->job->getName()}",
			"content" =>
			"{$this->job->getDescription()}
---------------------------
{$add}§lアビリティ§r §7- §d{$this->job->getAbility()->getName()}§f
§bクールタイム: §c{$abilityCooltime}秒§f
{$this->job->getAbility()->getDescription()}
§f---------------------------
§lスキル§r§7 - §d{$this->job->getSkill()->getName()}§f
§bクールタイム: §c{$skillCooltime}秒§f
{$this->job->getSkill()->getDescription()}
§f---------------------------",
			"buttons" => [
				[
					"text" => "§d§l特性",
				],
				[
					"text" => "§b§lスペル"
				],
				[
					"text" => "§aステータス"
				]
			]
		];
	}

	public function handleResponse(Player $player, $data): void {
		parent::handleResponse($player, $data);

		if ($player === $this->player) {
			if ($data !== null) {
				if ($data == 0) {
					$form = new JobIdentityForm($player, $this->job, $this->job->getIdentityGroup()->getAll());
					$form->setChildForm($this);
					$player->sendForm($form);
				} elseif ($data == 1) {
					$form = new SpellListForm($this->job->getSpells());
					$form->setChildForm($this);
					$player->sendForm($form);
				} elseif ($data == 2) {
					$form = new JobStatusForm($player, $this->job);
					$form->setChildForm($this);
					$player->sendForm($form);
				}
			}
		} else {
			Messanger::error($player, "Invalid Sender", Messanger::getIdFromObject($this, "handleResponse"));
		}
	}
}
