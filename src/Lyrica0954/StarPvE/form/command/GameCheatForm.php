<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\form\command;

use Lyrica0954\StarPvE\form\FormUtil;
use Lyrica0954\StarPvE\game\Game;
use pocketmine\form\Form;
use pocketmine\player\Player;

class GameCheatForm implements Form {

	public function __construct(protected Game $game) {
	}

	public function jsonSerialize(): mixed {
		return [
			"type" => "form",
			"title" => "ゲームサービス >> {$this->game->getWorld()->getFolderName()} >> チート",
			"content" => "",
			"buttons" => [
				[
					"text" => "詳細設定"
				],
				[
					"text" => "クローズ"
				],
				[
					"text" => "ゲームオーバー"
				],
				[
					"text" => "ウェーブクリア"
				],
				[
					"text" => "ウェーブモンスター再出現"
				],
				[
					"text" => "ゲームスタート"
				],
				[
					"text" => "パークを獲得"
				]
			]
		];
	}

	public function handleResponse(Player $player, $data): void {
		if ($data !== null) {
			if ($this->game->isClosed()) {
				$player->sendMessage("§cゲームはクローズされています");
				return;
			}

			$checkPlaying = function () use ($player) {
				$playing = $this->game->getStatus() === Game::STATUS_PLAYING;
				if ($playing) {
					return true;
				} else {
					$player->sendMessage("§cゲームはプレイ中ではありません！このサブコマンドを実行するにはプレイ中である必要があります");
					return false;
				}
			};

			$pref = "§9Cheat §7>> §a{$player->getName()} によって";

			switch ($data) {
				case 0:
					$player->sendMessage("Coming soon...");
					break;
				case 1:
					$this->game->end(5);
					$this->game->broadcastMessage($pref . "ゲームが強制終了されました");
					break;
				case 2:
					if (!$checkPlaying()) break;
					$this->game->gameover();
					$this->game->broadcastMessage($pref . "ゲームオーバーにされました");
					break;
				case 3:
					if (!$checkPlaying()) break;
					if (!$this->game->getWaveController()->getCooltimeHandler()->isActive()) {
						$this->game->getWaveController()->waveClear();
						$this->game->broadcastMessage($pref . "ウェーブがクリアされました");
					} else {
						$player->sendMessage("§c既にクールタイムハンドラーが動いています");
					}
					break;
				case 4:
					if (!$checkPlaying()) break;
					$wc = $this->game->getWaveController();
					if (!$wc->getCooltimeHandler()->isActive()) {
						$wave = $wc->getWave();
						$wc->spawnWaveMonster($wave);
						$this->game->broadcastMessage($pref . "モンスターが再出現されました");
					} else {
						$player->sendMessage("§c既にクールタイムハンドラーが動いています");
					}
					break;
				case 5:
					if ($this->game->getStatus() === Game::STATUS_IDLE) {
						$this->game->start();
						$this->game->broadcastMessage($pref . "ゲームが開始されました");
					} else {
					}
					break;
				case 6:
					break;
			}
		}
	}
}
