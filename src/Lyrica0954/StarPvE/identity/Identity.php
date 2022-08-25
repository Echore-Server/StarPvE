<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\identity;

use Lyrica0954\StarPvE\data\condition\Condition;
use Lyrica0954\StarPvE\job\player\PlayerJob;
use Lyrica0954\StarPvE\StarPvE;
use pocketmine\event\HandlerListManager;
use pocketmine\event\Listener;
use pocketmine\player\Player;
use pocketmine\Server;

abstract class Identity {

	public function close(): void {
		if ($this instanceof Listener) HandlerListManager::global()->unregisterAll($this);
	}

	protected function registerEvent(): void {
		if ($this instanceof Listener) Server::getInstance()->getPluginManager()->registerEvents($this, StarPvE::getInstance());
	}

	public function __construct() {
	}

	abstract public function apply(): void;

	abstract public function reset(): void;

	abstract public function isApplicable(): bool;

	abstract public function getName(): string;

	abstract public function getDescription(): string;
}
