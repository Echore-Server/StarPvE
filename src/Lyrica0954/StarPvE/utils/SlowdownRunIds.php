<?php

namespace Lyrica0954\StarPvE\utils;

class SlowdownRunIds {

	public static function get(string $class, int $numbered = 0): string {
		return md5($class . "-{$numbered}");
	}
}
