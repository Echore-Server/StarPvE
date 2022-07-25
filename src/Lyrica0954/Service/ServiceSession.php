<?php

declare(strict_types=1);

namespace Lyrica0954\Service;

use pocketmine\plugin\Plugin;
use pocketmine\plugin\PluginBase;

class ServiceSession {

	/**
	 * @var Service[]
	 */
	private array $services;

	protected bool $active;

	protected PluginBase $plugin;

	public function __construct(PluginBase $plugin) {
		$this->services = [];
		$this->plugin = $plugin;
		$this->active = false;
	}

	public function getPlugin(): PluginBase {
		return $this->plugin;
	}

	public function add(Service $service): void {
		if ($this->active) {
			throw new \Exception("cannot add service: session is active");
		}

		if ($service->getSession() !== $this) {
			throw new \Exception("Session are old");
		}
		$this->services[spl_object_hash($service)] = $service;
	}

	/**
	 * @return Service[]
	 */
	public function getServices(): array {
		return $this->services;
	}

	public function start(): void {
		$this->active = true;
		foreach ($this->services as $service) {
			$service->enable();
		}
	}

	public function shutdown(): void {
		$this->active = false;
		foreach ($this->services as $service) {
			$service->disable();
		}
	}

	public function restart(): void {
		$this->shutdown();
		$this->start();
	}
}
