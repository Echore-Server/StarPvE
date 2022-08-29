<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\player\party;

use pocketmine\player\Player;

class PartyInvite {

	public bool $expired = false;

	public function __construct(
		protected Party $party,
		protected Player $inviter,
		protected Player $victim,
		protected int $tick
	) {
	}

	public function getTick(): int {
		return $this->tick;
	}

	public function getParty(): Party {
		return $this->party;
	}

	public function getInviter(): Player {
		return $this->inviter;
	}

	public function onAccept(): void {
		$this->party->onAcceptInvite($this);
	}

	public function getVictim(): Player {
		return $this->victim;
	}

	public function isExpired(): bool {
		return $this->expired;
	}
}
