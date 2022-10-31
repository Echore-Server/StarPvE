<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\job\player\shaman;

use Lyrica0954\StarPvE\job\AbilitySpell;
use Lyrica0954\StarPvE\job\AbilityStatus;
use Lyrica0954\StarPvE\job\ActionListManager;
use Lyrica0954\StarPvE\job\ActionResult;
use Lyrica0954\StarPvE\job\LineOption;
use Lyrica0954\StarPvE\job\player\shaman\entity\SpiritEntity;
use Lyrica0954\StarPvE\translate\DescriptionTranslator;
use pocketmine\entity\Entity;
use pocketmine\entity\Location;
use pocketmine\event\entity\EntityDeathEvent;
use pocketmine\event\Listener;
use pocketmine\item\Item;
use pocketmine\item\VanillaItems;

class SpawnSpiritSpell extends AbilitySpell implements Listener {

	/**
	 * @var Entity[]
	 */
	protected array $entities = [];

	protected ?LineOption $line = null;

	const LINE_FORMAT = "§e霊体の数§f: §c%d §f/ §7%d";

	public function getName(): string {
		return "霊体召喚";
	}

	public function getDescription(): string {
		$damage = DescriptionTranslator::health($this->damage);
		return sprintf('§b発動時: §f戦闘を助けてくれる霊体(オオカミ)を召喚する。
体力: プレイヤーの最大体力と同じ。
ダメージ: %1$s
好物はパン。', $damage);
	}

	public function getActivateItem(): Item {
		return VanillaItems::HEART_OF_THE_SEA()->setCustomName("§r§l§b{$this->getName()}");
	}

	public function close(): void {
		parent::close();

		foreach ($this->entities as $entity) {
			$entity->close();
		}
	}

	/**
	 * @return Entity[]
	 */
	public function getEntities(): array {
		return $this->entities;
	}

	protected function init(): void {
		$this->amount = new AbilityStatus(5);
		$this->cooltime = new AbilityStatus(10.0 * 20);
		$this->damage = new AbilityStatus(1.3);

		$this->line = LineOption::immobile("None", -2);
		$this->getJob()->getActionListManager()->setLine($this->getJob()->getActionListManager()->getMax(), $this->line);
	}

	protected function sortAlive(): void {
		foreach ($this->entities as $k => $entity) {
			if ($entity->isClosed() || !$entity->isAlive()) {
				unset($this->entities[$k]);
			}
		}
	}

	protected function onActivate(): ActionResult {
		$amount = ((int) $this->amount->get());
		$this->sortAlive();

		if (count($this->entities) >= $amount) {
			return ActionResult::FAILED();
		}

		$entity = new SpiritEntity(Location::fromObject($this->player->getEyePos(), $this->player->getWorld()));
		$entity->setHelping($this->player);
		$entity->setOwningEntity($this->player);
		$entity->setHelperFollowRange(12);
		$entity->setMaxHealth($this->player->getMaxHealth());
		$entity->setHealth($entity->getMaxHealth());
		$entity->setAttackDamage($this->damage->get());
		$entity->spawnToAll();

		$this->entities[spl_object_hash($entity)] = $entity;

		$this->updateLine();

		return ActionResult::SUCCEEDED();
	}

	public function onEntityDeath(EntityDeathEvent $event) {
		$entity = $event->getEntity();
		if ($entity instanceof SpiritEntity) {
			unset($this->entities[spl_object_hash($entity)]);
			$this->updateLine();
		}
	}

	protected function updateLine(): void {
		$this->line->setText(sprintf(self::LINE_FORMAT, count($this->entities), (int) $this->amount->get()));
	}
}
