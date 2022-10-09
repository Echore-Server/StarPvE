<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\job\player\archer;

use Lyrica0954\StarPvE\event\game\GameStartEvent;
use Lyrica0954\StarPvE\job\Ability;
use Lyrica0954\StarPvE\job\AbilityStatus;
use Lyrica0954\StarPvE\job\ActionResult;
use Lyrica0954\StarPvE\job\player\archer\entity\ExplodeArrow;
use Lyrica0954\StarPvE\job\player\archer\entity\SpecialArrow;
use Lyrica0954\StarPvE\job\player\archer\entity\WoundArrow;
use Lyrica0954\StarPvE\job\player\archer\item\SpecialBow;
use Lyrica0954\StarPvE\job\player\PlayerJob;
use Lyrica0954\StarPvE\job\StatusTranslate;
use Lyrica0954\StarPvE\StarPvE;
use Lyrica0954\StarPvE\translate\DescriptionTranslator;
use Lyrica0954\StarPvE\utils\EffectGroup;
use Lyrica0954\StarPvE\utils\VectorUtil;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\entity\Location;
use pocketmine\entity\projectile\Arrow;
use pocketmine\entity\projectile\Projectile;
use pocketmine\event\entity\EntityShootBowEvent;
use pocketmine\event\entity\ProjectileLaunchEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerItemUseEvent;
use pocketmine\item\Bow;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\enchantment\VanillaEnchantments;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;
use pocketmine\item\ItemUseResult;
use pocketmine\network\mcpe\protocol\ItemComponentPacket;
use pocketmine\player\Player;
use pocketmine\world\sound\BowShootSound;
use Ramsey\Uuid\Type\Integer;

class SpecialBowAbility extends Ability implements Listener {

	const SIGNAL_ARROW_MULTIPLY = 0;
	const SIGNAL_ARROW_BOUNCE = 1;

	/**
	 * @var AbilityStatus
	 */
	protected AbilityStatus $explodeDamage;

	protected Item $bow;

	public function getName(): string {
		return "スーパーアロー";
	}

	public function getDescription(): string {
		$damage = DescriptionTranslator::health($this->damage);
		$area = DescriptionTranslator::number($this->area, "m");
		$explodeDamage = DescriptionTranslator::health($this->explodeDamage);
		$duration = DescriptionTranslator::second($this->duration);
		return
			sprintf('§6最大チャージ + スニーク時 ------------§f
クールタイム使用

§b発動時:§f 即着弾で、敵を貫通する矢を発射する。
当たった敵に %1$s ダメージと §d致命傷§f 状態を %2$s 与える。

§6通常時 ------------§f
クールタイムなし

§b発動時:§f 爆発矢を発射する。
当たった場所に小さな爆発を起こし %3$s 以内の敵にチャージに応じた %4$s ダメージを与える
', $damage, $duration, $area, $explodeDamage);
	}

	protected function init(): void {
		$this->duration = new AbilityStatus(7 * 20);
		$this->area = new AbilityStatus(2.0);
		$this->damage = new AbilityStatus(15.0);
		$this->explodeDamage = new AbilityStatus(8.9);
		$this->bow = ItemFactory::getInstance()->get(ItemIds::BOW);
		if ($this->bow instanceof Bow) {
			$this->bow->setUnbreakable(true);
			$this->bow->addEnchantment(new EnchantmentInstance(VanillaEnchantments::INFINITY()));
			$this->bow->setCustomName("§r§dSpecial Bow");
		}

		$this->cooltime = new AbilityStatus(18 * 20);
	}

	/**
	 * @return AbilityStatus
	 */
	public function getExplodeDamage(): AbilityStatus {
		return $this->explodeDamage;
	}

	public function getStatusList(int $status): ?array {
		$list = parent::getStatusList($status);
		if (is_null($list)) {
			return null;
		}

		if ($status === StatusTranslate::STATUS_DAMAGE) {
			$list[] = $this->explodeDamage;
		}

		return $list;
	}


	public function onGameStart(GameStartEvent $event) {
		$game = $event->getGame();
		if ($this->player instanceof Player) {
			$gp = StarPvE::getInstance()->getGamePlayerManager()->getGamePlayer($this->player);
			if ($gp?->getGame() === $game) {
				$arrow = ItemFactory::getInstance()->get(ItemIds::ARROW);

				$this->player->getInventory()->addItem($this->bow, $arrow);
			}
		}
	}

	public function onShoot(EntityShootBowEvent $event) {
		$projectile = $event->getProjectile();
		$entity = $event->getEntity();
		if ($projectile instanceof Arrow) {
			if ($entity === $this->player && $entity instanceof Player) {
				if ($event->getForce() == 3.0 && $entity->isSneaking()) {
					$result = $this->activate();
					if ($result->isSucceeded()) {
						$new = new WoundArrow($projectile->getLocation(), $projectile->getOwningEntity(), $projectile->isCritical(), $projectile->saveNBT());
						$new->area = $this->area->get();
						$new->hitDamage = $this->damage->get();
						$new->duration = (int) $this->duration->get();
						$event->setProjectile($new);
					} else {
						$event->cancel();
					}
				} else {
					$yaw = $entity->getLocation()->getYaw();
					$pitch = $entity->getLocation()->getPitch();
					$all = [];
					$range = 15;
					$count = 1 + $this->signal->get(self::SIGNAL_ARROW_MULTIPLY);
					if ($count <= 1) {
						$all[] = 0;
					} else {
						$step = ($range * 2) / ($count - 1);

						$start = -$range;

						for ($i = 0; $i < $count; $i++) {
							$diff = $start + $i * $step;
							$all[] = $diff;
						}
					}

					foreach ($all as $diff) {
						$dir = VectorUtil::getDirectionVector($yaw + $diff, $pitch);
						$new = new ExplodeArrow($projectile->getLocation(), $projectile->getOwningEntity(), $projectile->isCritical(), $projectile->saveNBT());
						$new->setMotion($dir->multiply($event->getForce()));
						$new->area = $this->area->get();
						$new->areaDamage = 1.15 + ($this->explodeDamage->get() * ($event->getForce() / 3.0));
						$new->bounceCount += $this->signal->get(self::SIGNAL_ARROW_BOUNCE);
						$new->spawnToAll();
					}

					$event->getProjectile()->flagForDespawn();
				}
			}
		}
	}

	protected function onActivate(): ActionResult {
		return ActionResult::SUCCEEDED_SILENT();
	}
}
