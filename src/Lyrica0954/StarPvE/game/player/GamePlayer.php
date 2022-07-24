<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\game\player;

use Lyrica0954\StarPvE\form\PerkIdentitiesForm;
use Lyrica0954\StarPvE\game\Game;
use Lyrica0954\StarPvE\game\player\equipment\ArmorEquipment;
use Lyrica0954\StarPvE\game\player\equipment\SwordEquipment;
use Lyrica0954\StarPvE\identity\IdentityGroup;
use Lyrica0954\StarPvE\StarPvE;
use Lyrica0954\StarPvE\utils\ArmorSet;
use Lyrica0954\StarPvE\utils\PlayerUtil;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;
use pocketmine\player\Player;

class GamePlayer {

    private Player $player;
    private ?Game $game;

    private SwordEquipment $swordEquipment;
    private ArmorEquipment $armorEquipment;

    protected IdentityGroup $identityGroup;

    protected int $perkAvailable;

    public function __construct(Player $player) {
        $this->player = $player;
        $this->game = null;

        $this->swordEquipment = new SwordEquipment($this);
        $this->armorEquipment = new ArmorEquipment($this);

        $this->identityGroup = new IdentityGroup();

        $this->perkAvailable = 0;
    }

    public function getPerkAvailable(): int {
        return $this->perkAvailable;
    }

    public function sendPerkForm(): void {
        if ($this->game !== null) {
            $identities = PerkIdentitiesForm::generateIdentities($this, $this->game->getWaveController()->getWave());
            $form = new PerkIdentitiesForm($this, $identities);
            $this->player->sendForm($form);
        }
    }

    public function setPerkAvailable(int $count): void {
        $this->perkAvailable = $count;
    }

    public function getSwordEquipment(): SwordEquipment {
        return $this->swordEquipment;
    }

    public function getArmorEquipment(): ArmorEquipment {
        return $this->armorEquipment;
    }

    public function getIdentityGroup(): IdentityGroup {
        return $this->identityGroup;
    }

    public function resetEquipment(): void {
        $this->swordEquipment->reset();
        $this->armorEquipment->reset();

        $this->swordEquipment->setLevelToInitialLevel();
        $this->armorEquipment->setLevelToInitialLevel();
    }

    public function resetAll(): void {
        $this->identityGroup->reset($this->player);
        $this->identityGroup = new IdentityGroup();
        $this->perkAvailable = 0;

        $this->resetEquipment();
    }

    public function refreshEquipment(): void {
        $this->swordEquipment->refresh();
        $this->armorEquipment->refresh();
    }

    public function getPlayer() {
        return $this->player;
    }

    public function getGame() {
        return $this->game;
    }

    public function setGame(?Game $game) {
        if ($this->game instanceof Game) {
            if (!$this->game->isClosed()) {
                $this->game->onPlayerLeave($this->player);
            }
        }
        $this->game = $game;
        if ($game instanceof Game && !$game?->isClosed()) {
            $game->onPlayerJoin($this->player);
        }
    }

    public function joinGame(Game $game) {
        PlayerUtil::reset($this->player);
        $this->resetAll();
        $this->player->teleport($game->getCenterPosition());

        $this->setGame($game);
    }

    public function leaveGame() {
        PlayerUtil::reset($this->player);
        PlayerUtil::teleportToLobby($this->player);

        $this->resetAll();
        $this->setGame(null);
    }

    public function setGameFromId(?string $id) {
        if ($id === null) {
            $this->setGame(null);
        } else {
            $gameManager = StarPvE::getInstance()->getGameManager();
            if (($game = $gameManager->getGame($id)) !== null) {
                $this->setGame($game);
            }
        }
    }
}
