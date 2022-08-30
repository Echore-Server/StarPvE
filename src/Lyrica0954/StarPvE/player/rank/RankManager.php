<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\player\rank;

use pocketmine\player\Player;
use pocketmine\utils\SingletonTrait;

class RankManager {
	use SingletonTrait {
		getInstance as Singleton__getInstance;
	}

	public static function getInstance(): self {
		return self::Singleton__getInstance();
	}


	/**
	 * @var array[]
	 */
	protected array $players;

	/**
	 * @var Rank[]
	 */
	protected array $list;

	public function __construct() {
		$this->register(RankIds::RANK_OWNER, new Rank("§6"));
		$this->register(RankIds::RANK_ADMIN, new Rank("§c"));
		$this->register(RankIds::RANK_BUILDER, new Rank("§a"));
		$this->register(RankIds::RANK_SUPPORTER, new Rank("§b"));
		$this->register(RankIds::RANK_TESTER, new Rank("§3"));
	}

	public function register(int $id, Rank $rank): void {
		if (isset($this->list[$id])) {
			throw new \Exception("cannot override");
		}
		$this->list[$id] = $rank;
	}

	public function get(int $id): ?Rank {
		return $this->list[$id] ?? null;
	}

	public function add(Player $player, Rank $rank): void {
		$xuid = $player->getXuid();
		$this->players[$xuid] ?? $this->players[$xuid] = [];
		$this->players[$xuid][] = clone $rank;
	}

	public function apply(Player $player): void {
		$ranks = $this->players[$player->getXuid()] ?? [];
		$name = $player->getName();
		foreach ($ranks as $rank) {
			$name = $rank->getPrefix() . $name;
		}

		$player->setDisplayName($name);
	}
}
