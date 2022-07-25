<?php

declare(strict_types=1);

namespace Lyrica0954\Service;

# 小さなサービスを提供するクラス

abstract class Service {

	private ServiceSession $session;

	protected bool $enable = false;

	public function __construct(ServiceSession $serviceSession) {
		$this->session = $serviceSession;
		$this->init();
	}

	public function getSession(): ServiceSession {
		return $this->session;
	}


	protected function init(): void {
	}

	public function enable(): void {
		if (!$this->enable) {
			$this->onEnable();
		}

		$this->enable = true;
	}

	public function disable(): void {
		if ($this->enable) {
			$this->onDisable();
		}

		$this->enable = false;
	}

	public function isEnabled(): bool {
		return $this->enable;
	}

	protected function onEnable(): void {
	}

	protected function onDisable(): void {
	}
}
