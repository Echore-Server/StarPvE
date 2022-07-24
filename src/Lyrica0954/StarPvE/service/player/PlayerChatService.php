<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\service\player;

use Lyrica0954\StarPvE\game\player\GamePlayer;
use Lyrica0954\StarPvE\service\ListenerService;
use Lyrica0954\StarPvE\StarPvE;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\player\Player;

class PlayerChatService extends ListenerService {

    public function onChat(PlayerChatEvent $event) {
        $player = $event->getPlayer();
        $format = $event->getFormat();

        if ($format == "chat.type.text") {
            $gamePlayer = StarPvE::getInstance()->getGamePlayerManager()->getGamePlayer($player);
            if ($gamePlayer instanceof GamePlayer) {
                $game = $gamePlayer->getGame();
                $channel = "None";
                $isForceGlobal = str_starts_with($event->getMessage(), "!");
                if ($game !== null && !$isForceGlobal) {
                    $channel = "ゲーム";
                    $event->setRecipients($game->getPlayers());
                } else {
                    $channel = "グローバル";
                }

                if ($isForceGlobal) {
                    $event->setMessage(substr($event->getMessage(), 1));
                }


                $message = "§8[{$channel}] §f<{$player->getDisplayName()}> §r{$event->getMessage()}";

                foreach ($event->getRecipients() as $commandSender) {
                    $commandSender->sendMessage($message);
                }

                $event->cancel();
            }
        }
    }
}
