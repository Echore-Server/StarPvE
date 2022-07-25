<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\data\adapter;

use pocketmine\block\Planks;
use pocketmine\player\Player;
use pocketmine\utils\Config;

class PlayerConfigAdapter extends SimpleConfigAdapter {

	protected string $xuid;

	public function __construct(string $xuid, Config $config) {
		parent::__construct($config);
		$this->xuid = $xuid;
	}

	public function getXuid(): string {
		return $this->xuid;
	}
}
