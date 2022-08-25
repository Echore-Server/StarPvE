<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\task;

use Lyrica0954\StarPvE\StarPvE;
use pocketmine\scheduler\Task;

trait TaskHolder {

	protected ?Task $task = null;

	private bool $taskSafety = true;

	protected function removeTask() {
		$this->task?->getHandler()->cancel();
		$this->task = null;
	}

	protected function isBusy() {
		return $this->task !== null;
	}

	protected function setTask(Task $task) {
		if ($this->taskSafety && $this->task instanceof Task) {
			$this->removeTask();
		}
		$this->task = $task;
	}

	protected function addTask(Task $task) {
		if (!$this->isBusy()) {
			$this->setTask($task);
			StarPvE::getInstance()->getScheduler()->scheduleTask($task);
		}
	}

	protected function addRepeatingTask(Task $task, int $period) {
		if (!$this->isBusy()) {
			$this->setTask($task);
			StarPvE::getInstance()->getScheduler()->scheduleRepeatingTask($task, $period);
		}
	}

	protected function addDelayedTask(Task $task, int $period) {
		if (!$this->isBusy()) {
			$this->setTask($task);
			StarPvE::getInstance()->getScheduler()->scheduleDelayedTask($task, $period);
		}
	}
}
