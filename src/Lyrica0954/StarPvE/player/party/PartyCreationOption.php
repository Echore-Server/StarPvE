<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\player\party;

use pocketmine\player\Player;

class PartyCreationOption {

	protected Player $host;

	/**
	 * @var Player[]
	 */
	protected array $players;

	/**
	 * @param Player $host
	 * @param Player[] $players
	 */
	public function __construct(Player $host, array $players) {
		$this->host = $host;
		$this->players = $players;
	}

	public function getHost(): Player {
		return $this->host;
	}

	/**
	 * @return Player[]
	 */
	public function getPlayers(): array {
		return $this->players;
	}
}
