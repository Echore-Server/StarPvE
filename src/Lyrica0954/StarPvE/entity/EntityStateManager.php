<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\entity;

use Lyrica0954\StarPvE\utils\TaskUtil;
use pocketmine\scheduler\ClosureTask;

class EntityStateManager {

	private static array $list = [];

	private static int $id = 0;

	public static function nextStateId(): int {
		return self::$id++;
	}

	public static function start(EntityState $state, int $stateId): void {
		$id = $state->getEntity()->getId();

		self::$list[$id] ?? self::$list[$id] = [];

		self::$list[$id][$stateId] = $state;
		$state->start();
	}

	public static function startWithDuration(EntityState $state, int $duration): void {
		if ($duration < 0) {
			throw new \InvalidArgumentException("duration < 0");
		}

		$id = self::nextStateId();

		TaskUtil::delayed(new ClosureTask(function () use ($state, $id) {
			self::end($state->getEntity()->getId(), $id);
		}), $duration);

		self::start($state, $id);
	}

	public static function has(int $entityRuntimeId, string $stateClass): bool {
		foreach (self::$list[$entityRuntimeId] ?? [] as $stateId => $state) {
			if ($state::class === $stateClass) {
				return true;
			}
		}

		return false;
	}

	public static function end(int $entityRuntimeId, int $stateId): void {
		self::$list[$entityRuntimeId][$stateId]?->close();

		unset(self::$list[$entityRuntimeId][$stateId]);
	}

	public static function clear(int $entityRuntimeId): void {
		foreach (self::$list[$entityRuntimeId] ?? [] as $stateId => $state) {
			self::end($entityRuntimeId, $stateId);
		}
	}
}
