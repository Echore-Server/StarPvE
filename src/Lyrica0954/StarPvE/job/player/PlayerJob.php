<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\job\player;

use Lyrica0954\StarPvE\identity\IdentityGroup;
use Lyrica0954\StarPvE\identity\player\PlayerArgIdentity;
use Lyrica0954\StarPvE\job\Ability;
use Lyrica0954\StarPvE\job\AbilitySpell;
use Lyrica0954\StarPvE\job\ActionBarManager;
use Lyrica0954\StarPvE\job\ActionListManager;
use Lyrica0954\StarPvE\job\Skill;
use Lyrica0954\StarPvE\job\cooltime\CooltimeAttachable;
use Lyrica0954\StarPvE\job\cooltime\CooltimeHandler;
use Lyrica0954\StarPvE\job\cooltime\CooltimeNotifier;
use Lyrica0954\StarPvE\job\IdentitySpell;
use Lyrica0954\StarPvE\job\Job;
use Lyrica0954\StarPvE\job\JobIdentityGroup;
use Lyrica0954\StarPvE\job\LineOption;
use Lyrica0954\StarPvE\job\Spell;
use Lyrica0954\StarPvE\StarPvE;
use Lyrica0954\StarPvE\utils\TaskUtil;
use pocketmine\entity\Skin;
use pocketmine\event\HandlerListManager;
use pocketmine\event\Listener;
use pocketmine\item\Item;
use pocketmine\item\ItemIds;
use pocketmine\player\Player;
use pocketmine\scheduler\TaskHandler;
use pocketmine\Server;

abstract class PlayerJob extends Job {

	protected ?Player $player = null;

	protected Ability $ability;
	protected Skill $skill;

	/**
	 * @var Spell[]
	 */
	protected array $spells;

	/**
	 * @var Spell[]
	 */
	protected array $defaultSpells;

	protected IdentityGroup $identityGroup;

	protected CooltimeNotifier $cooltimeNotifier;

	protected ActionListManager $action;
	protected int $lastActionUpdate;

	protected ?TaskHandler $actionTask;

	/**
	 * @return void
	 * 
	 * called when registered
	 */
	public static function initStatic(): void {
	}

	public function __construct(?Player $player = null) {

		if ($player instanceof Player) { #JobManager への登録を簡単にするため
			$this->player = $player;

			$this->log("§dCreated for {$player->getName()}");

			if ($this instanceof Listener) Server::getInstance()->getPluginManager()->registerEvents($this, StarPvE::getInstance());
		} else {
			$this->player = null;

			#$this->log("§dCreated for none");
		}
		$this->ability = $this->getInitialAbility();
		$this->skill = $this->getInitialSkill();
		$this->spells = [];
		$this->defaultSpells = [];
		$this->identityGroup = $this->getInitialIdentityGroup();
		$this->action = new ActionListManager();
		$this->lastActionUpdate = 0;
		if ($player instanceof Player) {
			foreach ($this->identityGroup->getAll() as $identity) {
				if ($identity instanceof PlayerArgIdentity) {
					$identity->setPlayer($player);
				}
			}
			$this->identityGroup->apply();

			$this->cooltimeNotifier = new CooltimeNotifier($player);
			$this->cooltimeNotifier->start();

			$this->actionTask = TaskUtil::repeatingClosure(function () use ($player) {
				if ($player instanceof Player) {
					$changed = $this->action->hasChanged();
					$this->action->update(1);
					#print_r($this->action->getSorted());
					if (($changed || Server::getInstance()->getTick() - $this->lastActionUpdate >= 40) && $this->action->hasContent()) {
						$this->lastActionUpdate = Server::getInstance()->getTick();

						$player->sendTip($this->action->getText());
					}

					#$player->sendMessage($this->action->hasChanged() ? "true" : "false");
				}
			}, 1);

			$this->init();

			$this->sortCooltimeNotifier();
		}
	}

	protected function init(): void {
	}

	/**
	 * @return Spell[]
	 */
	public function getDefaultSpells(): array {
		return $this->defaultSpells;
	}

	protected function sortCooltimeNotifier(): void {
		$all = [
			$this->ability->getCooltimeHandler(),
			$this->skill->getCooltimeHandler()
		];
		$spells = [];
		foreach ($this->spells as $spell) {
			if ($spell instanceof AbilitySpell) {
				$spells[] = $spell->getCooltimeHandler();
			}
		}

		$this->cooltimeNotifier->setAll(array_merge($all, $spells));
	}

	public function applyAllSpellEffect(): void {
		foreach ($this->spells as $spell) {
			if ($spell instanceof IdentitySpell) {
				$spell->getIdentityGroup()->apply();
			}
		}
	}

	public function resetAllSpellEffect(): void {
		foreach ($this->spells as $spell) {
			if ($spell instanceof IdentitySpell) {
				$spell->getIdentityGroup()->apply();
			}
		}
	}

	public function closeSpell(): void {
		foreach ($this->spells as $spell) {
			$spell->close();
		}
	}

	public function close() {
		$this->ability->close();
		$this->skill->close();
		$this->closeSpell();

		$this->resetAllSpellEffect();

		$this->cooltimeNotifier->stop();

		$this->identityGroup->reset();

		$this->identityGroup->close();

		$this->player = null;
		$this->actionTask?->cancel();
		$this->log("§dClosed");

		if ($this instanceof Listener) HandlerListManager::global()->unregisterAll($this);
	}

	public function onItemUse(Item $item) {
		if ($item->getId() === ItemIds::BOOK) {
			$activated = null;
			if ($this->player->isSneaking()) {
				$result = $this->skill->activate();
				$activated = $this->skill;
			} else {
				$result = $this->ability->activate();
				$activated = $this->ability;
			}

			$name = $activated->getCooltimeHandler()->getId();
			#$this->log("Activated {$name}");
			if ($result->isFailedByCooltime()) {
				$this->action->push(new LineOption("§c現在{$name}はクールタイム中です！"));
			} elseif ($result->isFailedAlreadyActive()) {
				$this->action->push(new LineOption("§c{$name}は既にアクティブです！"));
			} elseif ($result->isSucceeded()) {
				$this->action->push(new LineOption("§a{$name}を発動しました！"));
			} elseif ($result->isFailed()) {
				$this->action->push(new LineOption("§c{$name}を発動できません！"));
			} elseif ($result->isAbandoned()) {
				#bomb!
			}
		}

		foreach ($this->spells as $spell) {
			if ($spell instanceof AbilitySpell) {
				$spellItem = $spell->getActivateItem();
				if ($spellItem->equals($item, false, false)) {
					$result = $spell->activate();

					$name = $spell->getName();

					if ($result->isFailedByCooltime()) {
						$this->action->push(new LineOption("§c現在{$name}はクールタイム中です！"));
					} elseif ($result->isFailedAlreadyActive()) {
						$this->action->push(new LineOption("§c{$name}は既にアクティブです！"));
					} elseif ($result->isSucceeded()) {
						$this->action->push(new LineOption("§a{$name}を発動しました！"));
					} elseif ($result->isFailed()) {
						$this->action->push(new LineOption("§c{$name}を発動できません！"));
					} elseif ($result->isAbandoned()) {
					}
				}
			}
		}
	}

	public function getPlayer(): ?Player {
		return $this->player;
	}

	public function getCooltimeNotifier(): CooltimeNotifier {
		return $this->cooltimeNotifier;
	}

	public function getActionListManager(): ActionListManager {
		return $this->action;
	}

	public function getAbility(): Ability {
		return $this->ability;
	}

	public function getSkill(): Skill {
		return $this->skill;
	}

	public function setAbility(Ability $ability): void {
		$this->ability->close();
		$this->ability = $ability;
		$this->sortCooltimeNotifier();
	}

	public function setSkill(Skill $skill): void {
		$this->skill->close();
		$this->skill = $skill;
		$this->sortCooltimeNotifier();
	}

	public function addSpell(Spell $spell): void {
		$this->spells[] = $spell;
		if ($spell instanceof IdentitySpell) {
			$spell->getIdentityGroup()->apply();
		}

		$this->sortCooltimeNotifier();
	}

	/**
	 * @return Spell[]
	 */
	public function getSpells(): array {
		return $this->spells;
	}

	/**
	 * @param Spell[] $spells
	 * 
	 * @return void
	 */
	public function setSpells(array $spells): void {
		$this->resetAllSpellEffect();
		$this->spells = $spells;
		$this->applyAllSpellEffect();

		$this->sortCooltimeNotifier();
	}

	public function removeSpell(Spell $spell): void {
		$key = array_search($spell, $this->spells);
		if ($key !== false) {
			$target = $this->spells[$key];
			$target->close();
			if ($target instanceof IdentitySpell) {
				$target->getIdentityGroup()->reset();
			}

			unset($this->spells[$key]);
		}

		$this->sortCooltimeNotifier();
	}

	public function getIdentityGroup(): IdentityGroup {
		return $this->identityGroup;
	}

	abstract protected function getInitialAbility(): Ability;

	abstract protected function getInitialSkill(): Skill;

	abstract protected function getInitialIdentityGroup(): IdentityGroup;

	public function canActivateAbility(): bool {
		return !$this->ability->getCooltimeHandler()->isActive();
	}

	public function canActivateSkill(): bool {
		return !$this->skill->getCooltimeHandler()->isActive();
	}

	public function log(string $message) {
		StarPvE::getInstance()->log("§7[PlayerJob - {$this->getName()}] {$message}");
	}
}
