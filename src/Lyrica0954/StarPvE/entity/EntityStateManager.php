<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\entity;

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
