<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\game;

use Lyrica0954\StarPvE\game\stage\StageFactory;
use Lyrica0954\StarPvE\StarPvE;
use Lyrica0954\StarPvE\utils\WorldUtil;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\world\World;
use pocketmine\world\WorldManager;

class GameManager {

	protected array $games;

	public function __construct() {
		$this->games = [];
	}

	/**
	 * @return Game[]
	 */
	public function getGames(): array {
		return $this->games;
	}

	protected function addGame(Game $game, string $id = null) {
		if ($id === null) $id = $this->generateId(10);
		$this->games[$id] = $game;
	}

	protected function removeGame(String $id) {
		unset($this->games[$id]);
	}

	public function getGameFromWorld(World $world): ?Game {
		return $this->games[$world->getFolderName()] ?? null;
	}

	public function log(string $message) {
		StarPvE::getInstance()->log("§7[GameManager] {$message}");
	}

	public function createNewGame(GameCreationOption $option): ?string {
		$id = $option->getId();
		if (in_array($id, array_keys($this->games))) {
			return null;
		}

		$wm = Server::getInstance()->getWorldManager();
		$stageInfo = StageFactory::getInstance()->get($option->getStageName());
		if ($stageInfo === null) {
			return null;
		}

		$worldName = $stageInfo->getWorldName();
		if ($wm->isWorldLoaded($worldName)) {
			$wm->unloadWorld($wm->getWorldByName($worldName));
		}
		$folder = WorldUtil::cloneWorld($worldName, $id);
		if ($folder !== null) {
			$wm = Server::getInstance()->getWorldManager();
			$wm->loadWorld($id);
			$world = $wm->getWorldByName($id);
			$world->setAutoSave(false);
			$world->setTime(13000);
			$world->stopTime();
			$game = new Game($world, $stageInfo, $option->getGameOption());

			$this->addGame($game, $id);

			$game->finishedPrepare();
			return $id;
		}

		return null;
	}

	public function deleteUnusedWorld(): void {
		$stages = [];
		foreach (StageFactory::getInstance()->getList() as $stageInfo) {
			$stages[] = $stageInfo->getWorldName();
		}
		foreach (WorldUtil::getTrueWorlds() as $world) {
			Server::getInstance()->getWorldManager()->unloadWorld($world);
			if (in_array($world->getDisplayName(), $stages)) {
				if ($world->getDisplayName() != $world->getFolderName()) {
					$this->log("§6Deleted Unused World: {$world->getFolderName()}");
					WorldUtil::deleteWorld($world);
				}
			}
		}
	}

	public function generateId(Int $length) {
		return substr(str_shuffle("qwertyuiopasdfghjklzxcvbnm1234567890"), 0, $length);
	}

	public function getGame(string $id): ?Game {
		return $this->games[$id] ?? null;
	}

	public function cleanGame(string $id) {
		if (($game = $this->getGame($id)) !== null) {
			if ($game->getStatus() === Game::STATUS_IDLE) {
				foreach (StarPvE::getInstance()->getGamePlayerManager()->getGamePlayers() as $gamePlayer) {
					if ($gamePlayer->getGame() === $game) {
						$gamePlayer->leaveGame();
					}
				}
				$world = $game->getWorld();
				Server::getInstance()->getWorldManager()->unloadWorld($world);
				WorldUtil::deleteWorld($world);
				$this->removeGame($id);
			}
		}
	}


	public function cleanAll() {
		foreach ($this->games as $id => $game) {
			$this->cleanGame($id);
		}
	}
}
