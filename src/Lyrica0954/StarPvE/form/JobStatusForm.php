<?php


declare(strict_types=1);

namespace Lyrica0954\StarPvE\form;

use Lyrica0954\StarPvE\data\player\adapter\JobConfigAdapter;
use Lyrica0954\StarPvE\job\Job;
use Lyrica0954\StarPvE\job\player\PlayerJob;
use pocketmine\player\Player;

class JobStatusForm extends AdvancedForm {

	public function __construct(protected Player $player, protected Job $job) {
	}

	public function jsonSerialize(): mixed {
		$ref = new \ReflectionClass($this->job);
		$configName = $ref->getShortName();

		$adapter = JobConfigAdapter::fetch($this->player, $configName);
		$content = "";
		if ($adapter instanceof JobConfigAdapter) {
			foreach ([
				"モンスターキル: %s" => [
					JobConfigAdapter::MONSTER_KILLS
				],
				"死亡回数: %s" => [
					JobConfigAdapter::DEATHS
				],
				"ゲームプレイ: %s回" => [
					JobConfigAdapter::PLAY_COUNT
				],
				"勝利: %s回" => [
					JobConfigAdapter::GAME_WON
				],
				"敗北: %s回" => [
					JobConfigAdapter::GAME_LOST
				],
				"経験値: %s / %s EXP" => [
					JobConfigAdapter::EXP,
					JobConfigAdapter::NEXT_EXP
				],
				"合計獲得経験値: %sEXP" => [
					JobConfigAdapter::TOTAL_EXP
				],
				"職業レベル: %s" => [
					JobConfigAdapter::LEVEL
				],
			] as $format => $dataEntry) {
				$data = [];
				foreach ($dataEntry as $entry) {
					$data[] = $adapter->getConfig()->get($entry, null);
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
		} else {
		}

		return [
			"type" => "form",
			"title" => "ステータス >> 職業 >> {$configName}",
			"content" => $content,
			"buttons" => []
		];
	}
}
