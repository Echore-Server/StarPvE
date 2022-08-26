<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\job;

use Lyrica0954\StarPvE\job\cooltime\CooltimeHandler;
use Lyrica0954\StarPvE\job\player\PlayerJob;
use pocketmine\item\Item;
use pocketmine\player\Player;

interface Spell {

	public function getName(): string;

	public function close(): void;
}
