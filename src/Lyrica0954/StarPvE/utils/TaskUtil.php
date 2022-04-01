<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\utils;

use DaveRandom\CallbackValidator\ReturnType;
use Lyrica0954\StarPvE\StarPvE;
use pocketmine\scheduler\ClosureTask;
use pocketmine\scheduler\Task;
use pocketmine\scheduler\TaskHandler;

class TaskUtil {

	public static function delayed(Task $task, int $delay): TaskHandler{
		return StarPvE::getInstance()->getScheduler()->scheduleDelayedTask($task, $delay);
	}

	public static function repeatingClosure(\Closure $closure, int $period): TaskHandler{
		$task = new ClosureTask($closure);
		return self::repeating($task, $period);
	}

	public static function repeating(Task $task, int $period): TaskHandler{
		return StarPvE::getInstance()->getScheduler()->scheduleRepeatingTask($task, $period);
	}

	public static function repeatingClosureLimit(\Closure $closure, int $period, int $limit): TaskHandler{
		$task = new class($closure, $limit) extends Task {

			private \Closure $closure;
			private int $limit;
			private int $count;

			public function __construct(\Closure $closure, int $limit){
				$this->closure = $closure;
				$this->limit = $limit;
				$this->count = 0;
			}

			public function onRun(): void{
				$this->count ++;
				($this->closure)();

				if ($this->count >= $this->limit){
					$this->getHandler()->cancel();
				}
			}
		};

		return self::repeating($task, $period);
	}


	public static function reapeatingClosureCheck(\Closure $closure, int $period, \Closure $checker){
		$task = new class($closure, $checker) extends Task {

			private \Closure $closure;
			private \Closure $checker;

			public function __construct(\Closure $closure, \Closure $checker){
				$this->closure = $closure;
				$this->checker = $checker;
			}

			public function onRun(): void{
				$check = ($this->checker)();
				if ($check === false){
					$this->getHandler()->cancel();
				}

				($this->closure)();
			}
		};

		return self::repeating($task, $period);
	}
}