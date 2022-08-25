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
use pocketmine\player\GameMode;
use pocketmine\player\Player;

class GamePlayer {

	private Player $player;
	private ?Game $game;

	private SwordEquipment $swordEquipment;
	private ArmorEquipment $armorEquipment;

	protected IdentityGroup $identityGroup;

	protected int $perkAvailable;

	/**
	 * @var Identity[]
	 */
	protected array $perkIdentities;

	public function __construct(Player $player) {
		$this->player = $player;
		$this->game = null;

		$this->swordEquipment = new SwordEquipment($this);
		$this->armorEquipment = new ArmorEquipment($this);

		$this->identityGroup = new IdentityGroup();
		$this->perkIdentities = [];
		$this->rollPerkIdentities();

		$this->perkAvailable = 0;
	}

	public function getPerkAvailable(): int {
		return $this->perkAvailable;
	}

	public function sendPerkForm(bool $internal = true): void {
		if ($this->game !== null) {
			$form = new PerkIdentitiesForm($this, $this->perkIdentities, $internal);
			$this->player->sendForm($form);
		}
	}

	public function rollPerkIdentities(): void {
		$this->perkIdentities = PerkIdentitiesForm::generateIdentities($this);
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

		$job = StarPvE::getInstance()->getJobManager()->getJob($this->getPlayer());
		if ($job !== null) {
			$class = $job::class;
			StarPvE::getInstance()->getJobManager()->setJob($this->player, $class);
		}

		$this->rollPerkIdentities();

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
		if ($game->getStatus() == Game::STATUS_PLAYING) {
			$game->giveEquipments($this->player);
		}

		$this->setGame($game);
	}

	public function spectateGame(Game $game) {
		if ($this->game === null) {
			$this->player->teleport($game->getCenterPosition());
			$this->player->setGamemode(GameMode::SPECTATOR());
		}
	}

	public function leaveGame() {
		PlayerUtil::reset($this->player);
		PlayerUtil::teleportToLobby($this->player);

		$this->resetAll();
		$this->player->setGamemode(GameMode::ADVENTURE());
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
