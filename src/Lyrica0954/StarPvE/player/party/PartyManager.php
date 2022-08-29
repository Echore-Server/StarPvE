<?php


declare(strict_types=1);

namespace Lyrica0954\StarPvE\player\party;

use Lyrica0954\StarPvE\utils\TaskUtil;
use pocketmine\player\Player;
use pocketmine\scheduler\TaskHandler;
use pocketmine\Server;
use pocketmine\utils\SingletonTrait;

class PartyManager {
	use SingletonTrait {
		getInstance as Singleton__getInstance;
	}

	public static function getInstance(): self {
		return self::Singleton__getInstance();
	}

	/**
	 * @var (Party|null)[]
	 */
	protected array $list;

	/**
	 * @var array[]
	 */
	protected array $invites;

	protected ?TaskHandler $inviteExpireTask;

	public function __construct() {
		$this->list = [];
		$this->invites = [];

		$this->inviteExpireTask = TaskUtil::repeatingClosure(function () {
			foreach ($this->invites as $k1 => $senders) {
				foreach ($senders as $k2 => $invite) {
					$tick = Server::getInstance()->getTick();
					if ($tick - $invite->getTick() > (2 * 60 * 20)) {
						$invite->expired = true;
						unset($this->invites[$k1][$k2]);
						$invite->getInviter()->sendMessage("§dParty §7>> §c{$invite->getVictim()->getName()} への招待が無効になりました (2分経過)");
					}
				}
			}
		}, 60);
	}

	public function create(PartyCreationOption $option): bool {
		$host = $option->getHost();
		if ($this->hasParty($host)) {
			return false;
		}

		$party = new Party($host);

		foreach ($option->getPlayers() as $player) {
			$this->join($player, $party);
		}

		$this->setParty($option->getHost(), $party);
		return true;
	}

	public function publish(PartyInvite $invite): bool {
		$victim = $invite->getVictim();
		$inviter = $invite->getInviter();
		$party = $invite->getParty();
		$vxuid = $victim->getXuid();
		$ixuid = $inviter->getXuid();

		$this->invites[$vxuid] ?? $this->invites[$vxuid] = [];
		$ainvite = $this->invites[$vxuid][$ixuid] ?? null;
		if ($ainvite instanceof PartyInvite && !$ainvite->isExpired()) {
			return false;
		}
		$this->invites[$vxuid][$ixuid] = $invite;
		$victim->sendMessage("§dParty §7>> §a{$inviter->getName()} からパーティーに招待されました §7(/party accept {$inviter->getName()})");
		return true;
	}

	public function acceptInvite(Player $player, Player $inviter): void {
		$xuid = $player->getXuid();
		$ixuid = $inviter->getXuid();

		$invite = $this->invites[$xuid][$ixuid] ?? null;
		if ($invite instanceof PartyInvite && !$invite->isExpired()) {
			$success = $this->join($player, $invite->getParty());
			if ($success) {
				$invite->onAccept();
				$invite->expired = true;
				unset($this->invites[$xuid][$ixuid]);
			}
		} else {
			if ($invite?->isExpired() ?? false) {
				$player->sendMessage("§dParty §7>> §cこの招待は期限切れです！");
			} else {
				$player->sendMessage("§dParty §7>> §c{$inviter->getName()} からの招待はありません");
			}
		}
	}

	public function join(Player $player, Party $party): bool {
		if ($this->hasParty($player)) {
			return false;
		}

		$success = $party->join($player);

		if ($success) {
			$this->setParty($player, $party);
		}

		return $success;
	}

	public function disband(Party $party, ?Player $by = null): bool {
		$all = $party->getAll();
		$success = $party->disband($by);
		if ($success) {
			foreach ($all as $player) {
				$this->setParty($player, null);
			}
		}

		return $success;
	}

	protected function setParty(Player $player, ?Party $party): void {
		$this->leaveCurrent($player);
		$this->list[$player->getXuid()] = $party;
	}

	/**
	 * @param Player $player
	 * 
	 * @return Party|null
	 */
	public function get(Player $player): ?Party {
		$party = $this->list[$player->getXuid()] ?? null;
		if ($party instanceof Party && !$party->isDisbanded()) {
			return $party;
		} else {
			return null;
		}
	}

	public function hasParty(Player $player): bool {
		$party = $this->list[$player->getXuid()] ?? null;
		if ($party instanceof Party) {
			return !$party->isDisbanded();
		} else {
			return false;
		}
	}

	public function leaveCurrent(Player $player): void {
		$party = $this->list[$player->getXuid()] ?? null;
		if ($party !== null && !$party->isDisbanded()) {
			$party->leave($player);
			$this->list[$player->getXuid()] = null;
		}
	}
}
