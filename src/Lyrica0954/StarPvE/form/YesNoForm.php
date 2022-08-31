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

class YesNoForm extends AdvancedForm {

	public function __construct(protected string $content, private \Closure $callback) {
	}

	public function jsonSerialize(): mixed {
		return [
			"type" => "form",
			"title" => "確認",
			"content" => $this->content,
			"buttons" => [
				[
					"text" => "§aOK"
				],
				[
					"text" => "§cCancel"
				]
			]
		];
	}

	public function handleResponse(Player $player, $data): void {
		parent::handleResponse($player, $data);
		($this->callback)($player, $data);
	}
}
