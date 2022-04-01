<?php

declare(strict_types=1);

namespace Lyrica0954\Service;

use pocketmine\plugin\PluginBase;

# windows service host
# ServiceSession の管理をしやすくするクラス

class ServiceHost {

	protected PluginBase $plugin;

	protected ?ServiceSession $root;

	public function __construct(PluginBase $plugin){
		$this->plugin = $plugin;
		$this->root = null;
	}

	public function getPlugin(): PluginBase{
		return $this->plugin;
	}

	public function current(): ServiceSession{
		return $this->root;
	}

	public function open(): ServiceSession{
		if ($this->root instanceof ServiceSession){
			throw new \Exception("already open");
		}

		$this->root = new ServiceSession($this->plugin);
		return $this->root;
	}

	public function close(): void{
		$this->root->shutdown();
		$this->root = null;
	}
}