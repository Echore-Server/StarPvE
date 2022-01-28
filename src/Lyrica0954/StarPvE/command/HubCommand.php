<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\command;

use Lyrica0954\StarPvE\game\player\GamePlayer;
use Lyrica0954\StarPvE\StarPvE;
use Lyrica0954\StarPvE\utils\PlayerUtil;
use pocketmine\command\CommandSender;

final class HubCommand extends PluginCommandNoAuth {

    public function canRunBy(): int{
        return self::PLAYER;
    }


    protected function run(CommandSender $sender, array $args): void{
        $gamePlayer = StarPvE::getInstance()->getGamePlayerManager()->getGamePlayer($sender);
        if ($gamePlayer instanceof GamePlayer){
            PlayerUtil::flee($sender);
            $gamePlayer->leaveGame();
        } else {
            $sender->sendMessage("§cエラー: hubコマンドを実行できませんでした (error code: gameplayer)");
        }
    }
}