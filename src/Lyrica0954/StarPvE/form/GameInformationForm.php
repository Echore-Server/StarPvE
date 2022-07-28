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

class GameInformationForm implements Form {

	public function __construct(private Game $game) {
	}

	public function jsonSerialize(): mixed {

		$content = "";

		$g = $this->game;
		foreach ([
			"プレイ人数: %s/%s" => [count($g->getPlayers()), $g->getMaxPlayers()],
			"ステージ: %s §7(作成者: %s§7)" => [$g->getStageInfo()->getName(), $g->getStageInfo()->getAuthor()],
			"ゲームモード: §astandard§f" => [],
			"オブジェクト数: §a0§f" => []
		] as $format => $datas) {

			$list = [];
			foreach ($datas as $data) {
				$list[] = "§a{$data}§f";
			}


			$text = sprintf("§6" . $format, ...$list);

			$content .= "{$text}\n";
		}


		return [
			"type" => "form",
			"title" => "ゲームサービス >> {$this->game->getWorld()->getFolderName()}",
			"content" => $content,
			"buttons" => [
				[
					"text" => "§d参加する"
				],
				[
					"text" => "§9観戦する"
				],
				[
					"text" => "戻る"
				]
			]
		];
	}

	public function handleResponse(Player $player, $data): void {
		if ($data !== null) {
			if ($data == 0) {
				if ($this->game->canJoin($player)) {
					$gamePlayerManager = StarPvE::getInstance()->getGamePlayerManager();
					if (($gamePlayer = $gamePlayerManager->getGamePlayer($player)) !== null) {
						$gamePlayer->joinGame($this->game);
					} else {
						$player->sendMessage("§cエラー: ゲームに参加できませんでした");
					}
				} else {
					$player->sendMessage("§cこのゲームには参加できません");
				}
			} elseif ($data == 1) {
				$gamePlayerManager = StarPvE::getInstance()->getGamePlayerManager();
				if (($gamePlayer = $gamePlayerManager->getGamePlayer($player)) !== null) {
					$gamePlayer->spectateGame($this->game);
				} else {
					$player->sendMessage("§cエラー: ゲームを観戦できませんでした");
				}
			} else {
				TaskUtil::delayed(new ClosureTask(function () use ($player) {
					$jobSelect = new GameSelectForm();
					$player->sendForm($jobSelect);
				}), 1);
			}
		}
	}
}
