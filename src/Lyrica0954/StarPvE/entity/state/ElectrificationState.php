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
use pocketmine\entity\Entity;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\HandlerListManager;
use pocketmine\event\Listener;
use pocketmine\Server;
use pocketmine\world\Position;

class ElectrificationState extends ListenerState {

	public function __construct(Entity $entity, protected int $count, protected float $range) {
		parent::__construct($entity);
	}

	public function onDamage(EntityDamageEvent $event) {
		$entity = $event->getEntity();
		if ($entity === $this->entity && $event->getCause() !== DamageCause::CAUSE_ELECTRIFICATION) {
			$targets = array_filter(EntityUtil::getWithinRange($entity->getPosition(), $this->range, $entity), function (Entity $e): bool {
				return $e instanceof LivingBase;
			});

			for ($i = 0; $i < $this->count; $i++) {
				if (count($targets) > 0) {
					$k = array_rand($targets);
					$target = $targets[$k];

					ParticleUtil::send(
						new LineParticle(Position::fromObject($entity->getPosition()->add(0, 1.25, 0), $entity->getWorld()), 3),
						$entity->getWorld()->getPlayers(),
						Position::fromObject($target->getPosition()->add(0, 1.25, 0), $target->getWorld()),
						ParticleOption::spawnPacket("starpve:lightning_sparkler", "")
					);

					$source = new EntityDamageEvent($target, DamageCause::CAUSE_ELECTRIFICATION, $event->getBaseDamage() * 0.5, []);
					$target->attack($source);


					unset($targets[$k]);
				}
			}
		}
	}
}
