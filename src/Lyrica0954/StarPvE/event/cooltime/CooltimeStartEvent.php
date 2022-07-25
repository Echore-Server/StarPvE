<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\event\cooltime;

use Lyrica0954\StarPvE\job\cooltime\CooltimeHandler;
use pocketmine\event\Cancellable;
use pocketmine\event\CancellableTrait;

class CooltimeStartEvent extends CooltimeEvent implements Cancellable {
	use CancellableTrait;

	/**
	 * @var int
	 */
	protected int $cooltime;

	public function __construct(CooltimeHandler $handler, int $cooltime) {
		$this->handler = $handler;
		$this->cooltime = $cooltime;
	}

	public function getCooltime(): int {
		return $this->cooltime;
	}

	public function setCooltime(int $cooltime): void {
		$this->cooltime = $cooltime;
	}
}
