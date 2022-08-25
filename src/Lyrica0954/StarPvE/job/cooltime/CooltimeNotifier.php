<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\job\cooltime;

use Lyrica0954\StarPvE\StarPvE;
use pocketmine\player\Player;
use pocketmine\scheduler\Task;

class CooltimeNotifier {

	protected array $cooltimes;
	protected Player $player;

	private ?Task $task;

	protected string $prefix;

	public function __construct(Player $player) {
		$this->player = $player;
		$this->cooltimes = [];
		$this->task = null;
		$this->prefix = "";
	}

	/**
	 * @param CooltimeHandler[] $cooltimes
	 * 
	 * @return void
	 */
	public function setAll(array $cooltimes): void {
		$cooltimes = array_values($cooltimes);
		$final = [];
		foreach ($cooltimes as $handler) {
			$final[spl_object_hash($handler)] = $handler;
		}

		$this->cooltimes = $final;
	}

	/**
	 * @return CooltimeHandler[]
	 */
	public function getAll(): array {
		return $this->cooltimes;
	}

	public function getPrefix(): string {
		return $this->prefix;
	}

	public function setPrefix(string $prefix): void {
		$this->prefix = $prefix;
	}

	public function log(string $message) {
		StarPvE::getInstance()->log("§7[CooltimeNotifier] {$message}");
	}

	public function start() {
		$this->log("Started the Task");

		$this->task = new class($this) extends Task {

			private CooltimeNotifier $cooltimeNotifier;

			public function __construct(CooltimeNotifier $cooltimeNotifier) {
				$this->cooltimeNotifier = $cooltimeNotifier;
			}

			public function onRun(): void {
				$this->cooltimeNotifier->tick();
			}
		};
		StarPvE::getInstance()->getScheduler()->scheduleRepeatingTask($this->task, 20);
	}

	public function stop() {
		if ($this->task instanceof Task) {
			$this->task->getHandler()->cancel();
			$this->log("Stopped the Task");
		}
	}

	public function tick() {
		$text = $this->prefix;
		foreach ($this->cooltimes as $cooltimeHandler) {
			$seconds = round($cooltimeHandler->getRemain() / 20);
			$status = (!$cooltimeHandler->isActive() ? "§a使用可能" : "§c残り {$seconds}秒");
			$text .= "§7{$cooltimeHandler->getId()}: {$status}\n";
		}

		$this->player->sendPopup($text);
	}

	public function add(CooltimeHandler $cooltimeHandler) {
		$this->cooltimes[spl_object_hash($cooltimeHandler)] = $cooltimeHandler;
		$this->log("Added cooltime handler: {$cooltimeHandler->getId()}");
	}

	public function remove(CooltimeHandler $cooltimeHandler) {
		unset($this->cooltimes[spl_object_hash($cooltimeHandler)]);
		$this->log("Removed cooltime handler: {$cooltimeHandler->getId()}");
	}
}
