<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\game\player;

use pocketmine\player\Player;

class GamePlayerManager {

    private array $players;

    public function __construct() {
        $this->players = [];
    }

    public function getGamePlayers() {
        return $this->players;
    }

    public function addGamePlayer(Player $player) {
        if (!$this->isManaged($player)) {
            $this->players[spl_object_hash($player)] = new GamePlayer($player);
        }
    }

    public function removeGamePlayer(Player $player) {
        if ($this->isManaged($player)) {
            unset($this->players[spl_object_hash($player)]);
        }
    }

    public function isManaged(Player $player) {
        return isset($this->players[spl_object_hash($player)]);
    }

    public function getGamePlayer(Player $player): ?GamePlayer {
        return $this->players[spl_object_hash($player)] ?? null;
    }

    public function areSameGame(Player $a, Player $b) {
        return $this->getGamePlayer($a)?->getGame() === $this->getGamePlayer($b)?->getGame();
    }
}
