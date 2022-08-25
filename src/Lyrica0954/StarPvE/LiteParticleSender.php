<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE;

use Lyrica0954\MagicParticle\effect\ParticleEffect;
use Lyrica0954\MagicParticle\ParticleSender;
use Lyrica0954\StarPvE\data\adapter\PlayerConfigAdapter;
use Lyrica0954\StarPvE\data\player\SettingVariables;
use Lyrica0954\StarPvE\utils\TaskUtil;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\ClientboundPacket;
use pocketmine\network\mcpe\protocol\Packet;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\Server;
use pocketmine\world\Position;

class LiteParticleSender extends ParticleSender {

	/**
	 * @var int[]
	 */
	protected array $particleCount;

	/**
	 * @var int[]
	 */
	protected array $lastSend;

	public function __construct(PluginBase $plugin) {
		parent::__construct($plugin);
		$this->particleCount = [];
		$this->lastSend = [];
	}

	public function check(Player $player, Vector3 $pos): ?bool {

		$limitExceeded = false;

		$h = spl_object_hash($player);
		$lastSend = $this->lastSend[$h] ?? 0;
		$tick = Server::getInstance()->getTick();
		$ppt = $this->particleCount[$h] ?? 0;
		$adapter = SettingVariables::fetch($player);
		if ($adapter instanceof PlayerConfigAdapter) {
			$limit = $adapter->getConfig()->get(SettingVariables::PARTICLE_PER_TICK, 0);
		} else {
			$limit = 0;
		}
		if ($tick - $lastSend >= 1) {
			$this->particleCount[$h] = 0;
			$this->lastSend[$h] = $tick;
		}
		if ($ppt >= $limit) {
			$limitExceeded = true;
		} else {
			$this->particleCount[$h] ?? $this->particleCount[$h] = 0;
			$this->particleCount[$h]++;
		}

		return ($player->canInteract($pos, 16, M_SQRT3 / 3)) && !$limitExceeded;
	}
}
