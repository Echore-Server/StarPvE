<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\data\adapter;

use pocketmine\utils\Config;

class SimpleConfigAdapter {

	protected Config $config;

	public function __construct(Config $config){
		$this->config = $config;
	}

	public function getConfig(): Config{
		return $this->config;
	}

	public function addFloat($k, float $add): float{
		$v = $this->config->get($k);
		if (is_int($v) || is_float($v)){
			$v += $add;
			$this->config->set($k, $v);

			return $v;
		} else {
			throw new \Exception("expected int/float");
		}
	}

	public function addInt($k, int $add): int{
		$v = $this->config->get($k);
		if (is_int($v)){
			$v += $add;
			$this->config->set($k, $v);

			return $v;
		} else {
			throw new \Exception("expected int");
		}
	}

	public function subtractFloat($k, float $subtract): float{
		return $this->addFloat($k, -$subtract);
	}
}