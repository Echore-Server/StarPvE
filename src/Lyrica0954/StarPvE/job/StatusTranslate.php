<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\job;

final class StatusTranslate {

	const STATUS_DAMAGE = 0;
	const STATUS_AREA = 1;
	const STATUS_AMOUNT = 2;
	const STATUS_DURATION = 3;
	const STATUS_PERCENTAGE = 4;
	const STATUS_SPEED = 5;
	const STATUS_COOLTIME = 6;

	public static function translate(int $status): string {
		$text = match ($status) {
			self::STATUS_DAMAGE => "ダメージ",
			self::STATUS_AREA => "範囲",
			self::STATUS_AMOUNT => "量/回数",
			self::STATUS_DURATION => "効果時間",
			self::STATUS_PERCENTAGE => "倍率",
			self::STATUS_SPEED => "スピード",
			self::STATUS_COOLTIME => "クールタイム",
			default => "unknown"
		};

		return $text;
	}
}
