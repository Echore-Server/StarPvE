<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\service\ranking;

use Lyrica0954\Ranking\Ranking;
use Lyrica0954\Service\Service;
use Lyrica0954\Service\ServiceSession;
use Lyrica0954\StarPvE\particle\BoundFloatingTextParticle;
use Lyrica0954\StarPvE\player\ranking\RankingEntry;
use Lyrica0954\StarPvE\StarPvE;
use Lyrica0954\StarPvE\utils\Messanger;
use Lyrica0954\StarPvE\utils\TaskUtil;
use pocketmine\event\entity\EntityTeleportEvent;
use pocketmine\event\HandlerListManager;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\scheduler\TaskHandler;
use pocketmine\world\particle\FloatingTextParticle;
use pocketmine\world\Position;
use pocketmine\world\World;

class RankingUpdateService extends Service implements Listener {

	/**
	 * @var RankingEntry[]
	 */
	protected array $entries = [];

	/**
	 * @var FloatingTextParticle[]
	 */
	protected array $particles;

	protected World $world;

	protected ?TaskHandler $task = null;

	public function __construct(ServiceSession $serviceSession, World $world) {
		$this->world = $world;
		parent::__construct($serviceSession);
	}

	public function add(RankingEntry $entry): void {
		$this->entries[spl_object_hash($entry)] = $entry;
	}

	public function remove(RankingEntry $entry): void {
		unset($this->entries[spl_object_hash($entry)]);
	}

	public function update(): void {
		if (!$this->world->isLoaded()) {
			$this->task?->cancel();
			return;
		}

		foreach ($this->entries as $hash => $entry) {
			$checkEnabled = $entry->getManager()->isSorted();
			$beforeTop = $entry->getManager()->getTopRanking();
			$entry->update();
			$newTop = $entry->getManager()->getTopRanking();

			if ($newTop !== $beforeTop && $newTop instanceof Ranking && $checkEnabled) {
				$beat = "";
				if ($beforeTop instanceof Ranking) {
					$beat = " §e{$beforeTop->getDisplayName()}§r§7({$beforeTop->getValue()}) §fを抜かして";
				}

				$this->world->getServer()->broadcastMessage(Messanger::talk("Ranking", "{$entry->getName()} §r§fで §e{$newTop->getDisplayName()}§r§7({$newTop->getValue()}) §fが{$beat}一位になりました！"));
			}

			$pos = Position::fromObject($entry->getPosition(), $this->world);
			$particle = $this->particles[$hash] ?? null;

			$format = $entry->format();

			if (is_null($particle)) {
				$particle = new FloatingTextParticle("");
				$this->particles[$hash] = $particle;
			}

			$particle->setText($format);
			$particle->setTitle($entry->getName());

			$this->world->addParticle($pos, $particle);
		}
	}

	public function hideFor(Player $player): void {
		foreach ($this->particles as $hash => $particle) {
			$particle->setInvisible(true);
			$player->getWorld()->addParticle(Vector3::zero(), $particle, [$player]);
			$particle->setInvisible(false);
		}
	}

	public function sendFor(Player $player): void {
		foreach ($this->entries as $hash => $entry) {
			$particle = $this->particles[$hash] ?? null;
			if ($particle instanceof FloatingTextParticle) {
				$player->getWorld()->addParticle($entry->getPosition(), $particle, [$player]);
			}
		}
	}

	protected function onEnable(): void {
		$this->task = TaskUtil::repeatingClosure(function () {
			$this->update();
		}, (60 * 20));

		StarPvE::getInstance()->getServer()->getPluginManager()->registerEvents($this, StarPvE::getInstance());
	}

	/**
	 * @param PlayerJoinEvent $event
	 * 
	 * @return void
	 * 
	 * @priority MONITOR
	 */
	public function onJoin(PlayerJoinEvent $event): void {
		$player = $event->getPlayer();

		if ($player->getWorld() === $this->world) {
			$this->update();
		}
	}

	public function onTeleport(EntityTeleportEvent $event): void {
		$entity = $event->getEntity();

		if ($entity instanceof Player) {
			if ($event->getTo()->getWorld() !== $this->world) {
				$this->hideFor($entity);
			} else {
				$this->sendFor($entity);
			}
		}
	}

	protected function onDisable(): void {
		HandlerListManager::global()->unregisterAll($this);

		$this->task?->cancel();
	}
}
