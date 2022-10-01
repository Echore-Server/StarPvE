<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\utils;

use Closure;
use DaveRandom\CallbackValidator\ReturnType;
use Lyrica0954\StarPvE\StarPvE;
use pocketmine\scheduler\ClosureTask;
use pocketmine\scheduler\Task;
use pocketmine\scheduler\TaskHandler;
use pocketmine\utils\Utils;

class TaskUtil {

	/**
	 * @var TaskHandler[]
	 */
	protected static array $list = [];

	public static function delayed(Task $task, int $delay): TaskHandler {
		$handler = StarPvE::getInstance()->getScheduler()->scheduleDelayedTask($task, $delay);
		#self::$list[] = $handler;
		return $handler;
	}

	public static function repeatingClosure(\Closure $closure, int $period): TaskHandler {
		$task = new ClosureTask($closure);
		return self::repeating($task, $period);
	}

	public static function repeating(Task $task, int $period): TaskHandler {
		$handler = StarPvE::getInstance()->getScheduler()->scheduleRepeatingTask($task, $period);
		#self::$list[] = $handler;
		return $handler;
	}

	/**
	 * @return TaskHandler[]
	 */
	public static function getHandled(): array {
		return self::$list;
	}

	/**
	 * @return TaskHandler[]
	 */
	public static function getRunning(): array {
		$running = [];
		foreach (self::$list as $handler) {
			if (!$handler->isCancelled()) {
				$running[] = $handler;
			}
		}

		return $running;
	}

	public static function repeatingClosureLimit(\Closure $closure, int $period, int $limit): TaskHandler {
		$task = new class($closure, $limit) extends Task {

			private \Closure $closure;
			private int $limit;
			private int $count;

			public function __construct(\Closure $closure, int $limit) {
				$this->closure = $closure;
				$this->limit = $limit;
				$this->count = 0;
			}

			public function onRun(): void {
				$this->count++;
				($this->closure)();

				if ($this->count >= $this->limit) {
					$this->getHandler()->cancel();
				}
			}
		};

		return self::repeating($task, $period);
	}

	public static function repeatingClosureFailure(\Closure $closure, int $period): TaskHandler {
		$task = new class($closure) extends Task {

			private \Closure $closure;

			public function __construct(\Closure $closure) {
				Utils::validateCallableSignature(function (Closure $fail): void {
				}, $closure);
				$this->closure = $closure;
			}

			public function onRun(): void {
				$failed = false;
				$fail = function () use (&$failed) {
					$failed = true;
				};

				($this->closure)($fail);

				if ($failed) {
					$this->getHandler()->cancel();
				}
			}
		};

		return self::repeating($task, $period);
	}


	public static function repeatingClosureCheck(\Closure $closure, int $period, \Closure $checker): TaskHandler {
		$task = new class($closure, $checker) extends Task {

			private \Closure $closure;
			private \Closure $checker;

			public function __construct(\Closure $closure, \Closure $checker) {
				$this->closure = $closure;
				$this->checker = $checker;
			}

			public function onRun(): void {
				$check = ($this->checker)();
				if ($check === false) {
					$this->getHandler()->cancel();
					return;
				}

				($this->closure)();
			}
		};

		return self::repeating($task, $period);
	}
}
