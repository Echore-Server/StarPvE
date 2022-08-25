<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\data\player;

use Lyrica0954\StarPvE\data\adapter\PlayerConfigAdapter;
use Lyrica0954\StarPvE\data\adapter\SimpleConfigAdapter;
use Lyrica0954\StarPvE\data\player\adapter\GenericConfigAdapter;
use Lyrica0954\StarPvE\data\player\adapter\ItemConfigAdapter;
use Lyrica0954\StarPvE\data\player\adapter\JobConfigAdapter;
use pocketmine\block\Planks;
use pocketmine\player\Player;
use pocketmine\utils\Config;

class PlayerConfig {

	private GenericConfigAdapter $generic;
	/**
	 * @var JobConfigAdapter[]
	 */
	private array $jobs;

	private PlayerConfigAdapter $setting;

	private ItemConfigAdapter $bag;
	private ItemConfigAdapter $artifact;

	private string $xuid;

	/**
	 * @param Config $generic
	 * @param Config[] $jobs
	 * @param string $xuid
	 */
	public function __construct(Config $generic, Config $setting, Config $bag, Config $artifact, array $jobs, string $xuid) {
		$this->generic = new GenericConfigAdapter($xuid, $generic);
		$this->setting = new PlayerConfigAdapter($xuid, $setting);
		$this->bag = new ItemConfigAdapter($xuid, $bag);
		$this->artifact = new ItemConfigAdapter($xuid, $artifact, 6);
		$this->jobs = [];
		$this->xuid = $xuid;
		foreach ($jobs as $name => $jobConfig) {
			$this->jobs[strtolower($name)] = new JobConfigAdapter($xuid, $jobConfig);
		}
	}

	public function getGeneric(): GenericConfigAdapter {
		return $this->generic;
	}

	public function getSetting(): PlayerConfigAdapter {
		return $this->setting;
	}

	public function getBag(): ItemConfigAdapter {
		return $this->bag;
	}

	public function getArtifact(): ItemConfigAdapter {
		return $this->artifact;
	}

	/**
	 * @return JobConfigAdapter[]
	 */
	public function getJobs(): array {
		return $this->jobs;
	}

	public function getJob(string $name): ?JobConfigAdapter {
		return $this->jobs[strtolower($name)] ?? null;
	}

	public function addJob(string $name, Config $job): void {
		$this->jobs[strtolower($name)] = new JobConfigAdapter($this->xuid, $job);
	}
}
