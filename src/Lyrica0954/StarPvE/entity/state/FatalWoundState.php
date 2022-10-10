<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\entity\state;

use Lyrica0954\MagicParticle\LineParticle;
use Lyrica0954\MagicParticle\ParticleOption;
use Lyrica0954\SmartEntity\entity\LivingBase;
use Lyrica0954\StarPvE\entity\DamageCause;
use Lyrica0954\StarPvE\entity\EntityState;
use Lyrica0954\StarPvE\StarPvE;
use Lyrica0954\StarPvE\utils\EntityUtil;
use Lyrica0954\StarPvE\utils\ParticleUtil;
use Lyrica0954\StarPvE\utils\PlayerUtil;
use pocketmine\entity\Entity;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\HandlerListManager;
use pocketmine\event\Listener;
use pocketmine\Server;
use pocketmine\world\Position;

class FatalWoundState extends EntityState implements Listener {

	public function __construct(Entity $entity, protected float $multiplier) {
		parent::__construct($entity);
	}

	public function start(): void {
		Server::getInstance()->getPluginManager()->registerEvents($this, StarPvE::getInstance());
	}

	public function onDamage(EntityDamageEvent $event) {
		$entity = $event->getEntity();
		if ($entity === $this->entity && $event->canBeReducedByArmor()) {
			PlayerUtil::broadcastSound($entity, "mob.irongolem.repair", 1.2, 0.7);
			EntityUtil::multiplyFinalDamage($event, $this->multiplier);
		}
	}

	public function close(): void {
		HandlerListManager::global()->unregisterAll($this);
	}
}
