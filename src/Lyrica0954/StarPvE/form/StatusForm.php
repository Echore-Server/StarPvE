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

class StatusForm extends AdvancedForm {

	public function __construct(private Player $player) {
	}

	public function jsonSerialize(): mixed {
		$content = "";

		foreach ([
			"§c警告レベル: %s" => [
				GenericConfigAdapter::WARN
			],
			"モンスターキル: %s" => [
				GenericConfigAdapter::MONSTER_KILLS
			],
			"死亡回数: %s" => [
				GenericConfigAdapter::DEATHS
			],
			"ゲームプレイ: %s回" => [
				GenericConfigAdapter::PLAY_COUNT
			],
			"勝利: %s回" => [
				GenericConfigAdapter::GAME_WON
			],
			"敗北: %s回" => [
				GenericConfigAdapter::GAME_LOST
			],
			"経験値: %s / %s EXP" => [
				GenericConfigAdapter::EXP,
				GenericConfigAdapter::NEXT_EXP
			],
			"合計獲得経験値: %sEXP" => [
				GenericConfigAdapter::TOTAL_EXP
			],
			"レベル: %s" => [
				GenericConfigAdapter::LEVEL
			],
		] as $format => $dataEntry) {
			$data = [];
			foreach ($dataEntry as $entry) {
				$data[] = GenericConfigAdapter::fetch($this->player)?->getConfig()->get($entry, null);
			}

			$dataColored = array_map(function ($d) {
				if ($d === null) {
					return "§c§lエラー§f";
				} else {
					return "§a{$d}§f";
				}
			}, $data);
			$text = sprintf("§6" . $format, ...$dataColored);

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
		parent::handleResponse($player, $data);
		if ($data !== null) {
		}
	}
}
