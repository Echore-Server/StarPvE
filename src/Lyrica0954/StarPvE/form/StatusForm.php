<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\form;

use Lyrica0954\StarPvE\data\player\adapter\GenericConfigAdapter;
use Lyrica0954\StarPvE\game\player\GamePlayer;
use Lyrica0954\StarPvE\game\shop\content\ShopContent;
use Lyrica0954\StarPvE\game\shop\Shop;
use Lyrica0954\StarPvE\utils\PlayerUtil;
use pocketmine\form\Form;
use pocketmine\player\Player;

class StatusForm implements Form {

	public function __construct(private Player $player) {
	}

	public function jsonSerialize(): mixed {
		$content = "";

		foreach ([
			"モンスターキル: %s" => GenericConfigAdapter::MONSTER_KILLS,
			"死亡: %s" => GenericConfigAdapter::DEATHS,
			"ゲームプレイ: %s回" => GenericConfigAdapter::PLAY_COUNT,
			"勝利: %s回" => GenericConfigAdapter::GAME_WON,
			"敗北: %s回" => GenericConfigAdapter::GAME_LOST,
			"経験値: %sEXP" => GenericConfigAdapter::EXP,
			"合計獲得経験値: %sEXP" => GenericConfigAdapter::TOTAL_EXP,
			"レベル: %s" => GenericConfigAdapter::LEVEL,
			"レベルアップに必要な経験値: %sEXP" => GenericConfigAdapter::NEXT_EXP
		] as $format => $dataEntry) {
			$data = GenericConfigAdapter::fetch($this->player)?->getConfig()->get($dataEntry, null);

			if ($data === null) {
				$data = "§c<!>§f";
			}

			$text = sprintf("§6" . $format, "§a{$data}§f");

			$content .= "{$text}\n";
		}


		return [
			"type" => "form",
			"title" => "ステータス >> プレイヤー",
			"content" => $content,
			"buttons" => []
		];
	}

	public function handleResponse(Player $player, $data): void {
		if ($data !== null) {
		}
	}
}
