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

	public function getFromName(string $name): ?int {
		return $this->list[$name] ?? null;
	}

	/**
	 * @return int[]
	 */
	public function getAll(): array {
		return $this->list;
	}
}
