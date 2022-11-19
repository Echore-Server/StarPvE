<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\job\player\priest\state;

use Lyrica0954\StarPvE\entity\state\ListenerState;
use pocketmine\entity\Entity;
use pocketmine\event\entity\EntityEffectAddEvent;
use pocketmine\utils\Limits;

class EffectAmplificationState extends ListenerState {

	public function __construct(Entity $entity, protected float $durationAmp, protected float $levelAmp) {
		parent::__construct($entity);
	}

	public function onEffect(EntityEffectAddEvent $event) {
		$entity = $event->getEntity();
		$effect = $event->getEffect();
		if ($entity === $this->entity) {
			$effect->setDuration(min(Limits::INT32_MAX, (int) ($effect->getDuration() * $this->durationAmp)));
			$effect->setAmplifier(min(256, (int) ($effect->getEffectLevel() * $this->levelAmp)) - 1);
		}
	}
}
