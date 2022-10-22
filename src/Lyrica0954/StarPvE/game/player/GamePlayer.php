<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\game\player;

use Lyrica0954\StarPvE\form\PerkIdentitiesForm;
use Lyrica0954\StarPvE\form\SelectSpellForm;
use Lyrica0954\StarPvE\game\Game;
use Lyrica0954\StarPvE\game\player\equipment\ArmorEquipment;
use Lyrica0954\StarPvE\game\player\equipment\SwordEquipment;
use Lyrica0954\StarPvE\identity\IdentityGroup;
use Lyrica0954\StarPvE\job\IdentitySpell;
use Lyrica0954\StarPvE\job\player\PlayerJob;
use Lyrica0954\StarPvE\job\Spell;
use Lyrica0954\StarPvE\player\party\Party;
use Lyrica0954\StarPvE\player\party\PartyManager;
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

	protected int $masteryAvailable;

	/**
	 * @var Identity[]
	 */
	protected array $perkIdentities;

	/**
	 * @var Spell[]
	 */
	protected array $masterySpells;

	public function __construct(Player $player) {
		$this->player = $player;
		$this->game = null;

		$this->swordEquipment = new SwordEquipment($this);
		$this->armorEquipment = new ArmorEquipment($this);

		$this->identityGroup = new IdentityGroup();
		$this->perkIdentities = [];
		$this->masterySpells = [];
		$this->rollPerkIdentities();
		$this->rollMasterySpells();

		$this->perkAvailable = 0;
		$this->masteryAvailable = 0;
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

	public function sendMasteryForm(): void {
		$playerJob = StarPvE::getInstance()->getJobManager()->getJob($this->player);
		if ($playerJob instanceof PlayerJob) {
			$form = new SelectSpellForm($playerJob, $this->masterySpells);
			$form->setChildForm($form);
			$this->player->sendForm($form);
		}
	}

	public function rollPerkIdentities(): void {
		$this->perkIdentities = PerkIdentitiesForm::generateIdentities($this);
	}

	public function rollMasterySpells(): void {
		$playerJob = StarPvE::getInstance()->getJobManager()->getJob($this->player);
		if (!$playerJob instanceof PlayerJob) {
			return;
		}

		$all = $playerJob->getMasterySpells();
		$count = min(3, count($all));

		$result = [];
		for ($i = 0; $i < $count; $i++) {
			$k = array_rand($all);
			$spell = clone $all[$k];
			if ($spell instanceof IdentitySpell && !$spell->isApplicable()) {
				continue;
			}

			unset($all[$k]);
			$result[] = $spell;
		}

		$this->masterySpells = $result;
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

	protected function setGame(?Game $game) {
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
		$this->player->setGamemode(GameMode::ADVENTURE());
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

	public function leaveGameInternal() {
		$this->setGame(null);
		$this->resetAll();
	}

	public function leaveGame() {
		PlayerUtil::reset($this->player);
		PlayerUtil::teleportToLobby($this->player);

		StarPvE::getInstance()->getJobManager()->setJob($this->player, null);

		$this->player->setGamemode(GameMode::ADVENTURE());
		$this->leaveGameInternal();
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

	/**
	 * Get the value of masteryAvailable
	 *
	 * @return int
	 */
	public function getMasteryAvailable(): int {
		return $this->masteryAvailable;
	}

	/**
	 * Set the value of masteryAvailable
	 *
	 * @param int $masteryAvailable
	 *
	 * @return self
	 */
	public function setMasteryAvailable(int $masteryAvailable): self {
		$this->masteryAvailable = $masteryAvailable;

		return $this;
	}
}
