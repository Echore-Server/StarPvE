<?php

declare(strict_types=1);

namespace Lyrica0954\BossBar;

use pocketmine\block\Planks;
use pocketmine\network\mcpe\protocol\BossEventPacket;
use pocketmine\network\mcpe\protocol\types\BossBarColor;
use pocketmine\player\Player;
use pocketmine\world\World;

class BossBar {

	protected float $healthPercent;

	protected string $title;

	protected int $color;

	/**
	 * @var Player[]
	 */
	protected array $players;

	public function __construct(string $title) {
		$this->healthPercent = 1.0;
		$this->title = $title;
		$this->color = BossBarColor::PURPLE;
		$this->players = [];
	}

	public function getHealthPercent(): float {
		return $this->healthPercent;
	}

	public function getTitle(): string {
		return $this->title;
	}

	public function getColor(): int {
		return $this->color;
	}

	public function setHealthPercent(float $healthPercent): void {
		$this->healthPercent = $healthPercent;
	}

	public function setTitle(string $title): void {
		$this->title = $title;
	}

	public function setColor(int $color): void {
		$this->color = $color;
	}

	public function update(): void {
		foreach ($this->players as $player) {
			$session = $player->getNetworkSession();
			$session->sendDataPacket($this->getPercentPacket($player, $this->healthPercent));
			$session->sendDataPacket($this->getTitlePacket($player, $this->title));
		}
	}

	public function updateAll(): void {
		foreach ($this->players as $player) {
			$session = $player->getNetworkSession();
			$session->sendDataPacket($this->getHidePacket($player));
			$session->sendDataPacket($this->getShowPacket($player));
		}
	}

	private function getPercentPacket(Player $player, float $healthPercent): BossEventPacket {
		return BossEventPacket::healthPercent($player->getId(), $healthPercent);
	}

	private function getTitlePacket(Player $player, string $title): BossEventPacket {
		return BossEventPacket::title($player->getId(), $title);
	}

	private function getShowPacket(Player $player): BossEventPacket {
		$pk = BossEventPacket::show($player->getId(), $this->title, $this->healthPercent, 0, $this->color);
		return $pk;
	}

	private function getHidePacket(Player $player): BossEventPacket {
		$pk = BossEventPacket::hide($player->getId());
		return $pk;
	}

	public function showToWorld(World $world): void {
		foreach ($world->getPlayers() as $player) {
			if ($player->isOnline()) {
				$this->showToPlayer($player);
			}
		}
	}

	public function showToPlayer(Player $player): void {
		$this->players[] = $player;
		$player->getNetworkSession()->sendDataPacket($this->getShowPacket($player));
	}

	public function isShowed(Player $player): bool {
		return array_search($player, $this->players) !== false;
	}

	public function hide(): void {
		foreach ($this->players as $player) {
			$this->hideFromPlayer($player);
		}
	}

	public function hideFromPlayer(Player $player): void {
		$key = array_search($player, $this->players);
		if ($key !== false) {
			unset($this->players[$key]);
		}
		$player->getNetworkSession()->sendDataPacket($this->getHidePacket($player));
	}
}
