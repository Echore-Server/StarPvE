<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\command;

use Lyrica0954\StarPvE\game\Game;
use Lyrica0954\StarPvE\game\player\GamePlayer;
use Lyrica0954\StarPvE\StarPvE;
use Lyrica0954\StarPvE\utils\Messanger;
use Lyrica0954\StarPvE\utils\PlayerUtil;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use Ramsey\Uuid\Type\Integer;

final class GameCommand extends PluginCommandNoAuth {

	public function canRunBy(): int {
		return self::PLAYER | self::CONSOLE;
	}


	protected function run(CommandSender $sender, array $args): void {
		if (true) {
			if (count($args) > 0) {
				$sub = strtolower($args[0]);

				$manager = StarPvE::getInstance()->getGameManager();
				if ($sender instanceof Player) {
					$currentGame = $manager->getGameFromWorld($sender->getWorld());
				} else {
					$currentGame = null;
				}
				switch ($sub) {
					case "list":
						foreach ($manager->getGames() as $game) {
							$stat = Game::statusAsText($game->getStatus());
							$players = count($game->getPlayers());
							$max = $game->getMaxPlayers();
							$sender->sendMessage("§7{$game->getWorld()->getFolderName()} §d{$players}/{$max} §f- {$stat}");
						}
						break;
					case 'close':
						if (count($args) > 1) {
							$gameSelector = strtolower($args[1]);
							if ($gameSelector == "current") {
								if ($currentGame instanceof Game) {
									$currentGame->end(6);
									$sender->sendMessage("§aゲームサービス {$currentGame->getWorld()->getFolderName()} をクローズしました");
								} else {
									$sender->sendMessage("§aあなたは現在ゲームにいません");
								}
							} elseif ($gameSelector == "all") {
								foreach ($manager->getGames() as $game) {
									$game->end(6);
									$sender->sendMessage("§aゲームサービス {$game->getWorld()->getFolderName()} をクローズしました");
								}
							} else {
								$game = $manager->getGame($gameSelector);
								if ($game instanceof Game) {
									$game->end(6);
									$sender->sendMessage("§aゲームサービス {$game->getWorld()->getFolderName()} をクローズしました");
								} else {
									$sender->sendMessage("§cゲームサービス {$gameSelector} は有効化/実行されていません");
								}
							}
						}
						break;
					case 'create':
						$id = null;
						if (count($args) > 1) {
							$id = $args[1];
							if (strlen($id) !== mb_strlen($id, "utf-8")) {
								Messanger::error($sender, "ゲームIDに特殊文字は指定できません", "user");
							} else {
								if (strlen($id) <= 16) {
									$manager->createNewGame($id);
									$sender->sendMessage("§aゲーム {$id} を開始しました");
								} else {
									Messanger::error($sender, "ゲームIDは16文字以下である必要があります", "user");
								}
							}
						} else {
							$id = $manager->createNewGame(null);
							$sender->sendMessage("§aゲーム {$id} を開始しました");
						}
						break;
					case 'createcount':
						if (count($args) > 1) {
							$count = (int) $args[1];

							for ($i = 0; $i <= $count; $i++) {
								$id = $manager->createNewGame(null);
								$sender->sendMessage("§aゲーム {$id} を開始しました");
							}
						}
						break;
					case 'forcekick':
						if (count($args) > 1) {
							$playerName = $args[1];
							$player = $sender->getServer()->getPlayerExact($playerName);
							if ($player instanceof Player) {
								$gamePlayer = StarPvE::getInstance()->getGamePlayerManager()->getGamePlayer($player);
								if ($gamePlayer instanceof GamePlayer) {
									$game = $gamePlayer->getGame();
									if ($game instanceof Game) {
										$id = $game->getWorld()->getFolderName();
										$gamePlayer->leaveGame();
										$sender->sendMessage("§aゲームプレイヤー {$player->getName()} を参加中のゲームサービス {$id} から強制退出させました");
									} else {
										Messanger::error($sender, "プレイヤーはゲームに参加していません", "user");
									}
								}
							} else {
								Messanger::error($sender, "プレイヤーが見つかりません", "user");
							}
						}
				}
			}
		}
	}
}
