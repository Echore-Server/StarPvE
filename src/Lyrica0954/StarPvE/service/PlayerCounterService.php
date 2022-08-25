<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\service;

use Lyrica0954\Service\Service;
use pocketmine\player\Player;
use pocketmine\Server;

class PlayerCounterService extends Service {

	/**
	 * @var int[]
	 */
	private array $list = [];

	protected function onEnable(): void {
		$this->list = [];
	}

	public function add(Player $player, int $count = 1): void {
		$this->allocate($player);
		$this->list[$player->getName()] += $count;
	}

	public function allocate(Player $player, int $default = 0): void {
		$h = $player->getName();
		if (!isset($this->list[$h])) {
			$this->list[$h] = $default;
		}
	}

	public function subtract(Player $player, int $count = 1): void {
		$this->allocate($player);
		$this->list[$player->getName()] -= $count;
	}

	public function get(Player $player): int {
		$this->allocate($player);
		return $this->list[$player->getName()];
	}

	/**
	 * @return int[]
	 */
	public function getAll(): array {
		return $this->list;
	}

	public function createRanking(string $format = "§e%d位 §c%s §f- §c%d§f"): string {
		$list = $this->list;
		arsort($list, SORT_NUMERIC);
		$n = 0;
		$text = "";
		foreach ($list as $name => $count) {
			$n++;
			$text .= sprintf($format, $n, $name, $count) . "\n";
		}

		return $text;
	}
}
