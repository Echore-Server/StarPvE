<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\game\player;

use Lyrica0954\MagicParticle\ParticleOption;
use Lyrica0954\MagicParticle\SingleParticle;
use Lyrica0954\MagicParticle\utils\MolangUtil;
use Lyrica0954\StarPvE\form\PerkIdentitiesForm;
use Lyrica0954\StarPvE\form\SelectSpellForm;
use Lyrica0954\StarPvE\game\Game;
use Lyrica0954\StarPvE\game\player\equipment\ArmorEquipment;
use Lyrica0954\StarPvE\game\player\equipment\SwordEquipment;
use Lyrica0954\StarPvE\game\shop\content\PerkContent;
use Lyrica0954\StarPvE\game\shop\content\PrestageContent;
use Lyrica0954\StarPvE\identity\IdentityGroup;
use Lyrica0954\StarPvE\job\IdentitySpell;
use Lyrica0954\StarPvE\job\player\PlayerJob;
use Lyrica0954\StarPvE\job\Spell;
use Lyrica0954\StarPvE\player\party\Party;
use Lyrica0954\StarPvE\player\party\PartyManager;
use Lyrica0954\StarPvE\StarPvE;
use Lyrica0954\StarPvE\utils\ArmorSet;
use Lyrica0954\StarPvE\utils\ParticleUtil;
use Lyrica0954\StarPvE\utils\PlayerUtil;
use Lyrica0954\StarPvE\utils\TaskUtil;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;
use pocketmine\player\GameMode;
use pocketmine\player\Player;
use pocketmine\scheduler\TaskHandler;
use pocketmine\world\particle\FloatingTextParticle;
use pocketmine\world\Position;

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

	protected ?TaskHandler $task;

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

		$memory = new \stdClass;
		$memory->particle = new FloatingTextParticle("");
		$memory->lastExclamation = false;

		$this->task = TaskUtil::repeatingClosure(function () use ($memory) {
			if ($this->game instanceof Game) {
				$shop = $this->game->getShop();

				$particle = $memory->particle;

				/**
				 * @var FloatingTextParticle $particle
				 */

				$exclamation = false;
				$message = "";

				foreach ($shop->getContents() as $content) {
					if ($content instanceof PerkContent) {
						if ($content->canBuy($this->player)) {
							$exclamation = true;
							$message = "§eパークが購入できます";
							break;
						}
					}

					if ($content instanceof PrestageContent) {
						if ($content->canBuy($this->player)) {
							$exclamation = true;
							$message = "§eプレステージが購入できます";
							break;
						}
					}
				}
				$pos = $this->game->getVillager()?->getEyePos()?->add(0, 1.25, 0);

				if ($exclamation) {
					$molang = [];
					$molang[] = MolangUtil::variable("lifetime", 4);

					if ($pos !== null) {
						ParticleUtil::send(
							new SingleParticle,
							[$this->player],
							Position::fromObject($pos->add(0, 1, 0), $this->game->getWorld()),
							ParticleOption::spawnPacket("starpve:exclamation_mark", MolangUtil::encode($molang))
						);
					}
					$particle->setTitle($message);
					$particle->setInvisible(false);
				} else {
					$particle->setInvisible(true);
				}

				if ($memory->lastExclamation !== $exclamation) {
					$this->game->getWorld()->addParticle($pos, $particle, [$this->player]);
				}

				$memory->lastExclamation = $exclamation;
			}
		}, 80);
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
		$this->close();
	}

	public function leaveGame() {
		PlayerUtil::reset($this->player);
		PlayerUtil::teleportToLobby($this->player);

		StarPvE::getInstance()->getJobManager()->setJob($this->player, null);

		$this->player->setGamemode(GameMode::ADVENTURE());
		$this->setGame(null);
		$this->resetAll();
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

	public function close(): void {
		$this->resetAll();
		$this->task?->cancel();
	}
}
