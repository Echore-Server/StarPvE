<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\job\player\shaman;

use Lyrica0954\MagicParticle\CircleParticle;
use Lyrica0954\MagicParticle\ParticleOption;
use Lyrica0954\MagicParticle\SingleParticle;
use Lyrica0954\StarPvE\data\condition\Condition;
use Lyrica0954\StarPvE\game\wave\DefaultMonsters;
use Lyrica0954\StarPvE\game\wave\MonsterData;
use Lyrica0954\StarPvE\identity\IdentityGroup;
use Lyrica0954\StarPvE\job\Ability;
use Lyrica0954\StarPvE\job\AlwaysAbility;
use Lyrica0954\StarPvE\job\player\PlayerJob;
use Lyrica0954\StarPvE\job\player\swordman\ForceFieldSkill;
use Lyrica0954\StarPvE\job\Skill;
use Lyrica0954\StarPvE\utils\EntityUtil;
use Lyrica0954\StarPvE\utils\ParticleUtil;
use Lyrica0954\StarPvE\utils\PlayerUtil;
use pocketmine\entity\Entity;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\Listener;
use pocketmine\player\Player;

class Shaman extends PlayerJob implements Listener, AlwaysAbility {

	public function getName(): string {
		return "Shaman";
	}

	public function getDescription(): string {
		return
			"§7- §l§c戦闘§r

範囲攻撃を得意とする職業で、攻撃もかなり強力だが
アビリティなどが特殊で扱いが難しく上級者向け。";
	}

	public function getAlAbilityName(): String {
		return "デスコラプス";
	}

	public function getAlAbilityDescription(): String {
		return
			"§b発動条件:§f 敵を倒す
§b発動時:§f 倒した敵から半径 §c2.8m§f 以内の敵に
倒した敵の最大体力 §c16%% §f分のダメージを与える。
倒した敵が §dクリーパー§f の場合は範囲が §c10m§f になり、ダメージが最大体力 §c40%%§f 分になる。";
	}

	protected function getInitialAbility(): Ability {
		return new DownPulseAbility($this);
	}

	protected function getInitialSkill(): Skill {
		return new DeathPulseSkill($this);
	}

	protected function getInitialIdentityGroup(): IdentityGroup {
		return new IdentityGroup();
	}

	public function getSelectableCondition(): ?Condition {
		return null;
	}

	protected function causeCollapse(Entity $entity, int $count = 0): void {
		$pos = $entity->getPosition();
		$pos->y += 0.75;
		$players = $entity->getWorld()->getPlayers();
		ParticleUtil::send(new SingleParticle, $players, $pos, ParticleOption::spawnPacket("minecraft:splash_spell_emitter", ""));

		$range = match ($entity::class) {
			DefaultMonsters::CREEPER => 10.0,
			default => 2.8
		};
		$per = match ($entity::class) {
			DefaultMonsters::CREEPER => 0.4,
			default => 0.16
		};

		$damage = $entity->getMaxHealth() * $per;

		ParticleUtil::send(new CircleParticle($range, 4, unstableRate: 0.05), $players, $pos, ParticleOption::spawnPacket("minecraft:obsidian_glow_dust_particle", ""));

		PlayerUtil::broadcastSound($pos, "dig.basalt", 0.8, 1.0);

		if ($count < 2) {
			foreach (EntityUtil::getWithinRange($pos, $range) as $target) {
				if (MonsterData::isMonster($target)) {
					if ($target !== $entity) {
						if ($target->getHealth() > 0) {
							$source = new EntityDamageByEntityEvent($this->player, $target, EntityDamageEvent::CAUSE_ENTITY_ATTACK, $damage, [], 0);
							$source->setAttackCooldown(0);
							if ($target->getHealth() <= $source->getFinalDamage()) {
								$target->kill();
								$this->causeCollapse($target, $count + 1);
							} else {
								$target->attack($source);
							}
						}
					}
				}
			}
		}
	}

	/**
	 * @param EntityDamageByEntityEvent $event
	 * 
	 * @return void
	 * 
	 * @priority MONITOR
	 */
	public function onEntityDamageByEntity(EntityDamageByEntityEvent $event): void {
		$entity = $event->getEntity();
		$damager = $event->getDamager();
		if ($damager === $this->player && $this->player instanceof Player) {
			if ($entity->getHealth() <= $event->getFinalDamage() && $entity->isAlive()) {
				$this->causeCollapse($entity);
			}
		}
	}

	# collapse 	dig.vines
}
