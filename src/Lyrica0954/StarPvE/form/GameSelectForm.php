<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\form;

use Lyrica0954\StarPvE\game\Game;
use Lyrica0954\StarPvE\StarPvE;
use pocketmine\form\Form;
use pocketmine\player\Player;
use pocketmine\Server;

class GameSelectForm implements Form {

    private array $games;

    public function __construct() {
        $this->games = StarPvE::getInstance()->getGameManager()->getGames();
    }

    public function jsonSerialize(): mixed {
        $buttons = [];
        $gameCount = 0;
        foreach ($this->games as $game) {
            $playerCount = count($game->getPlayers());
            $statusText = Game::statusAsText($game->getStatus());
            $gameCount++;
            $buttons[] = [
                "text" => "§eGame {$gameCount}§d({$playerCount}/{$game->getMaxPlayers()}) §r{$statusText}\n§7{$game->getWorld()->getFolderName()}"
            ];
        }

        $color = ($gameCount >= 3) ? "§c" : "§a";
        $buttons[] = [
            "text" => "{$color}ニューゲーム §d({$gameCount}/3)"
        ];
        return [
            "type" => "form",
            "title" => "§a§lゲームセレクト",
            "content" => "",
            "buttons" => $buttons
        ];
    }

    public function handleResponse(Player $player, $data): void {
        if ($data !== null) {
            if (($game = (array_values($this->games)[$data] ?? null)) !== null) {
                if ($game->canJoin($player)) {
                    $gamePlayerManager = StarPvE::getInstance()->getGamePlayerManager();
                    if (($gamePlayer = $gamePlayerManager->getGamePlayer($player)) !== null) {
                        $gamePlayer->joinGame($game);
                    } else {
                        $player->sendMessage("§cエラー: ゲームに参加できませんでした");
                    }
                } else {
                    $player->sendMessage("§cこのゲームには参加できません");
                }
            } else {
                if ($data == (count($this->games))) {
                    $games = StarPvE::getInstance()->getGameManager()->getGames();
                    if (count($games) < 3) {
                        $id = StarPvE::getInstance()->getGameManager()->createNewGame();
                        if ($id !== null) {
                            $game = StarPvE::getInstance()->getGameManager()->getGame($id);
                            $stageInfo = $game->getStageInfo();
                            $player->sendMessage("§aゲームを作成しました！ §7(ID: {$id})");
                            $player->sendMessage("§aステージ: §b{$stageInfo->getName()}§7(作成者: {$stageInfo->getAuthor()})");
                        } else {
                            $player->sendMessage("§cエラー: ゲームを作成できませんでした (error code: folder)");
                        }
                    } else {
                        $player->sendMessage("§c新しいゲームを作成できません！ ゲーム数は最大3つまでです！");
                    }
                } else {
                    $player->sendMessage("§cエラー (error code: button)");
                }
            }
        }
    }
}
