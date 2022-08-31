<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\form\command;

use Lyrica0954\StarPvE\data\adapter\PlayerConfigAdapter;
use Lyrica0954\StarPvE\data\player\SettingVariables;
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

class SettingForm implements Form {


	public function __construct(private Player $player) {
	}

	public function jsonSerialize(): mixed {

		$contents = [];

		$adapter = SettingVariables::fetch($this->player);
		$ppt = 250;
		$debugDamage = false;
		if ($adapter instanceof PlayerConfigAdapter) {
			$ppt = $adapter->getConfig()->get(SettingVariables::PARTICLE_PER_TICK, 250);
			$debugDamage = $adapter->getConfig()->get(SettingVariables::DEBUG_DAMAGE, false);
		}

		$contents[] = FormUtil::slider("最大パーティクル数(1ティック中)", 0, 400, 5, $ppt);
		$contents[] = FormUtil::toggle("与える/受けるダメージの詳細表示", $debugDamage);

		return [
			"type" => "custom_form",
			"title" => "ユーザー設定",
			"content" => $contents,
		];
	}

	public function handleResponse(Player $player, $data): void {
		if ($data !== null) {
			$particlePerTick = (int) $data[0];
			$debugDamage = (bool) $data[1];

			$adapter = SettingVariables::fetch($player);
			if ($adapter instanceof PlayerConfigAdapter) {
				$adapter->getConfig()->set(SettingVariables::PARTICLE_PER_TICK, $particlePerTick);
				$adapter->getConfig()->set(SettingVariables::DEBUG_DAMAGE, $debugDamage);
			}

			$player->sendMessage("§a変更を保存しました");
		} else {
			$player->sendMessage("§c変更を破棄しました");
		}
	}
}
