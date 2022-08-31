<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\form\command;

use Lyrica0954\StarPvE\form\FormUtil;
use Lyrica0954\StarPvE\game\Game;
use Lyrica0954\StarPvE\game\GameCreationOption;
use Lyrica0954\StarPvE\game\GameOption;
use Lyrica0954\StarPvE\game\stage\StageFactory;
use Lyrica0954\StarPvE\StarPvE;
use Lyrica0954\StarPvE\utils\Messanger;
use Lyrica0954\StarPvE\utils\TaskUtil;
use pocketmine\form\Form;
use pocketmine\player\Player;
use pocketmine\scheduler\ClosureTask;
use pocketmine\Server;

class GameCreationForm implements Form {

	public function __construct() {
	}

	public function jsonSerialize(): mixed {

		$contents = [];

		$stageNames = array_keys(StageFactory::getInstance()->getList());

		$contents[] = FormUtil::input("ゲームID (windowsで予約されているものは使用しないでください)", "デフォルト: ランダム");
		$contents[] = FormUtil::dropdown("ステージ", $stageNames);
		$contents[] = FormUtil::slider("ゲームの最大参加人数", 1, 32, 1, 6);
		$contents[] = FormUtil::slider("ゲーム開始の最低人数", 1, 32, 1, 1);
		$contents[] = FormUtil::slider("経験値倍率", 0, 25.0, 0.2, 1);

		return [
			"type" => "custom_form",
			"title" => "ゲームサービス >> ゲームの作成",
			"content" => $contents,
		];
	}

	public function handleResponse(Player $player, $data): void {
		if ($data !== null) {
			$gameId = (string) $data[0];
			$stageNameIndex = (string) $data[1];
			$stageNames = array_keys(StageFactory::getInstance()->getList());
			$stageName = $stageNames[$stageNameIndex] ?? null;
			if ($stageName === null) {
				Messanger::error($player, "Invalid Stage Index", Messanger::getIdFromObject($this, "handleResponse"));
				return;
			}
			$maxPlayers = (int) $data[2];
			$minPlayers = (int) $data[3];
			$xpMultiplier = (float) $data[4];

			if ($minPlayers > $maxPlayers) {
				$player->sendMessage("§c最大参加人数より最低人数を大きくすることはできません");
				return;
			}

			if ($gameId === null || $gameId === "") {
				$gameId = GameCreationOption::genId(10);
			}
			#print_r($data);
			$option = new GameCreationOption($gameId, $stageName, new GameOption($maxPlayers, $minPlayers, $xpMultiplier));
			StarPvE::getInstance()->getGameManager()->createNewGame($option);
			$player->sendMessage("§aゲームを作成しました！");
		}
	}
}
