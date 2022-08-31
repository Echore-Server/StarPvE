<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\job\player\swordman;

use Lyrica0954\MagicParticle\effect\LightningEffect;
use Lyrica0954\MagicParticle\ParticleOption;
use Lyrica0954\StarPvE\game\wave\MonsterData;
use Lyrica0954\StarPvE\job\AbilitySpell;
use Lyrica0954\StarPvE\job\AbilityStatus;
use Lyrica0954\StarPvE\job\ActionResult;
use Lyrica0954\StarPvE\job\Skill;
use Lyrica0954\StarPvE\job\Spell;
use Lyrica0954\StarPvE\translate\DescriptionTranslator;
use Lyrica0954\StarPvE\utils\EntityUtil;
use Lyrica0954\StarPvE\utils\ParticleUtil;
use Lyrica0954\StarPvE\utils\PlayerUtil;
use Lyrica0954\StarPvE\utils\SlowdownRunIds;
use pocketmine\entity\Entity;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\Listener;
use pocketmine\item\Item;
use pocketmine\item\Sword;
use pocketmine\item\VanillaItems;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\AddActorPacket;
use pocketmine\network\mcpe\protocol\PlayerStartItemCooldownPacket;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;
use pocketmine\world\Position;
use Ramsey\Uuid\Rfc4122\Validator;

class StrikeSpell extends AbilitySpell {

	public function getActivateItem(): Item {
		$item = VanillaItems::NETHER_STAR()->setCustomName("§r§l§b{$this->getName()}");
		return $item;
	}

	public function getName(): string {
		return "ストライク";
	}

	protected function init(): void {
		$this->duration = new AbilityStatus(6 * 20);
		$this->percentage = new AbilityStatus(0.0);
		$this->area = new AbilityStatus(5.0);
		$this->damage = new AbilityStatus(2.0);
	}

	protected function onActivate(): ActionResult {
		PlayerUtil::broadcastSound($this->player, "ambient.weather.thunder", 1.6, 0.15);
		PlayerUtil::broadcastSound($this->player, "ambient.weather.lightning.impact", 1.3, 0.3);
		PlayerUtil::broadcastSound($this->player, "random.grass", 0.7, 1.0);
		foreach (EntityUtil::getWithinRange($this->player->getPosition(), $this->area->get()) as $entity) {
			if (MonsterData::isMonster($entity)) {
				$ppos = $entity->getPosition()->add(0, $entity->getEyeHeight(), 0);
				$par = new LightningEffect(Position::fromObject($ppos->add(0, 4, 0), $entity->getWorld()), 0.5, 1);
				ParticleUtil::send($par, $entity->getWorld()->getPlayers(), Position::fromObject($ppos, $entity->getWorld()), ParticleOption::spawnPacket("starpve:lightning_sparkler", ""));
				$source = new EntityDamageByEntityEvent($this->player, $entity, EntityDamageEvent::CAUSE_ENTITY_ATTACK, $this->damage->get(), [], 0);
				$entity->attack($source);
				EntityUtil::slowdown($entity, (int) $this->duration->get(), max(0.0, 0.5 - $this->percentage->get()), SlowdownRunIds::get($this::class));
			}
		}
		return ActionResult::SUCCEEDED();
	}

	public function getCooltime(): int {
		return 10 * 20;
	}

	public function getDescription(): string {
		$area = DescriptionTranslator::number($this->area, "m");
		$damage = DescriptionTranslator::health($this->damage);
		$duration = DescriptionTranslator::second($this->duration);
		$percentage = DescriptionTranslator::percentage($this->percentage, false, 0.5);
		return
			sprintf('§b発動時:§f %1$s 以内の敵に %2$s ダメージを与えて
 %3$s 秒間移動速度を %4$s 低下させる。', $area, $damage, $duration, $percentage);
	}
}
