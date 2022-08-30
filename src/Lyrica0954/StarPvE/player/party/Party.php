<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\player\party;

use Lyrica0954\StarPvE\utils\Messanger;
use pocketmine\player\Player;
use pocketmine\player\PlayerInfo;
use pocketmine\Server;

class Party {

	protected Player $host;

	/**
	 * @var Player[]
	 */
	protected array $players;

	protected bool $isDisbanded;

	/**
	 * @var PlayerInvite[]
	 */
	protected array $invites;

	public function __construct(Player $host) {
		$this->host = $host;

		$this->players = [];
		$this->isDisbanded = false;
		$this->invites = [];
	}

	public function getHost(): Player {
		return $this->host;
	}

	/**
	 * @return Player[]
	 */
	public function getPlayers(): array {
		return $this->players;
	}

	/**
	 * @return PartyInvite[]
	 */
	public function getPublishedInvites(): array {
		return $this->invites;
	}

	/**
	 * @return PartyInvite[]
	 */
	public function getActiveInvites(): array {
		return array_filter($this->invites, function (PartyInvite $invite) {
			return !$invite->isExpired();
		});
	}

	public function publishInvite(Player $inviter, Player $victim): ?PartyInvite {
		if ($this->host !== $inviter) {
			return null;
		}

		if ($inviter === $victim) {
			return null;
		}

		if ($this->search($victim)) {
			return null;
		}

		$invite = new PartyInvite($this, $inviter, $victim, Server::getInstance()->getTick());
		$this->invites[] = $invite;
		return $invite;
	}

	/**
	 * @return Player[]
	 */
	public function getAll(): array {
		return array_merge($this->players, [$this->host]);
	}

	public function isDisbanded(): bool {
		return $this->isDisbanded;
	}

	public function search(Player $player): bool {
		return array_search($player, $this->getAll()) !== false;
	}

	public function disband(?Player $by = null): bool {
		if ($by !== $this->host) {
			return false;
		}

		$this->isDisbanded = true;

		$this->onDisband($by);

		foreach ($this->players as $k => $player) {
			unset($this->players[$k]);
			$this->onLeave($player);
		}

		return true;
	}

	public function broadcastMessage(string $message): void {
		foreach ($this->getAll() as $player) {
			$player->sendMessage($message);
		}
	}

	public function join(Player $player): bool {
		$result = array_search($player, $this->getAll());
		if ($result === false) {
			$this->players[] = $player;
			$this->onJoin($player);
			return true;
		}
		return false;
	}

	public function leave(Player $player): bool {
		if ($this->host === $player) {
			$this->disband($player);
			return true;
		}

		$result = array_search($player, $this->players);
		if ($result === false) {
			return false;
		}

		unset($this->players[$result]);
		$this->onLeave($player);
		return true;
	}

	public function kick(Player $victim, ?Player $by = null): bool {
		if ($victim === $by) {
			return false;
		}

		if ($victim === $this->host) {
			return false;
		}

		if ($by !== $this->host) {
			return false;
		}

		$victim->sendMessage(Messanger::talk("Party", "§c追放されました"));
		$this->leave($victim);
		$this->onKick($victim, $by);
		return true;
	}

	public function onJoin(Player $player): void {
		$this->broadcastMessage(Messanger::talk("Party", "§a{$player->getName()} が参加しました"));
	}

	public function onLeave(Player $player): void {
		$this->broadcastMessage(Messanger::talk("Party", "§c{$player->getName()} が去りました"));
	}

	public function onAcceptInvite(PartyInvite $invite): void {
		$this->broadcastMessage(Messanger::talk("Party", "§a{$invite->getInviter()->getName()} の招待により {$invite->getVictim()->getName()} が参加しました"));
	}

	public function onDisband(?Player $by = null): void {
		$info = $by instanceof Player ? "{$by->getName()} によって" : "";
		$this->broadcastMessage(Messanger::talk("Party", "§c{$info}パーティーは解散されました"));
	}

	public function onKick(Player $victim, ?Player $by = null): void {
		$info = $by instanceof Player ? "{$by->getName()} によって" : "";
		$this->broadcastMessage(Messanger::talk("Party", "§c{$info} {$victim->getName()} が追放されました"));
	}
}
