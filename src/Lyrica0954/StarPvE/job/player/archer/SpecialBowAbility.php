<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\job\player\archer;

use Lyrica0954\StarPvE\event\game\GameStartEvent;
use Lyrica0954\StarPvE\job\Ability;
use Lyrica0954\StarPvE\job\AbilityStatus;
use Lyrica0954\StarPvE\job\ActionResult;
use Lyrica0954\StarPvE\job\player\archer\entity\SpecialArrow;
use Lyrica0954\StarPvE\job\player\archer\item\SpecialBow;
use Lyrica0954\StarPvE\job\player\PlayerJob;
use Lyrica0954\StarPvE\StarPvE;
use Lyrica0954\StarPvE\translate\DescriptionTranslator;
use Lyrica0954\StarPvE\utils\EffectGroup;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\entity\Location;
use pocketmine\entity\projectile\Arrow;
use pocketmine\entity\projectile\Projectile;
use pocketmine\event\entity\EntityShootBowEvent;
use pocketmine\event\entity\ProjectileLaunchEvent;
use pocketmine\event\Listener;
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

	/**
	 * @var AbilityStatus
	 */
	protected AbilityStatus $areaDamage;

	/**
	 * @var EffectGroup
	 */
	protected EffectGroup $areaEffects;

	/**
	 * @var EffectGroup
	 */
	protected EffectGroup $hitEffects;

	protected Item $bow;

	public function getCooltime(): int {
		return (int) (1.0 * 20);
	}

	public function getName(): string {
		return "トキシックアロー";
	}

	public function getDescription(): string {
		$damage = DescriptionTranslator::health($this->damage);
		$hitEffect = DescriptionTranslator::effectGroup($this->hitEffects);
		$area = DescriptionTranslator::number($this->area, "m");
		$areaDamage = DescriptionTranslator::health($this->areaDamage);
		$areaEffect = DescriptionTranslator::effectGroup($this->areaEffects);
		$duration = DescriptionTranslator::second($this->duration);
		return
			sprintf('§b発動時:§f 有毒な矢を放ち、敵に当たると %1$s のダメージと %2$s を与える。地面に当たった場合は、§b効果§f を発動させる。
矢は §dプレイヤー§f と §d村人§f を貫通する。
§b効果:§f 矢から %3$s 以内の敵に %4$s のダメージと %5$s を与える有毒ガスを %6$s 間放出する。', $damage, $hitEffect, $area, $areaDamage, $areaEffect, $duration);
	}

	protected function init(): void {
		$this->area = new AbilityStatus(2.6);
		$this->duration = new AbilityStatus(1 * 20);
		$this->damage = new AbilityStatus(6.0);
		$this->areaDamage = new AbilityStatus(2.0);
		$this->bow = ItemFactory::getInstance()->get(ItemIds::BOW);
		if ($this->bow instanceof Bow) {
			$this->bow->setUnbreakable(true);
			$this->bow->addEnchantment(new EnchantmentInstance(VanillaEnchantments::INFINITY()));
			$this->bow->setCustomName("§r§dToxic Bow");
		}
		$this->areaEffects = new EffectGroup(
			new EffectInstance(VanillaEffects::SLOWNESS(), 3 * 20, 1, false),
			new EffectInstance(VanillaEffects::WEAKNESS(), 3 * 20, 0, false)
		);
		$this->hitEffects = new EffectGroup(
			new EffectInstance(VanillaEffects::SLOWNESS(), 7 * 20, 2, false),
			new EffectInstance(VanillaEffects::WEAKNESS(), 3 * 20, 1, false)
		);
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
				} else {
					$result = $this->activate();
					if ($result->isSucceeded()) {
						$new = new SpecialArrow($projectile->getLocation(), $projectile->getOwningEntity(), $projectile->isCritical(), $projectile->saveNBT());
						$new->areaDamage = $this->areaDamage->get();
						$new->setBaseDamage($this->damage->get());
						$new->duration = (int) $this->duration->get();
						$new->area = $this->area->get();
						$new->period = 10;
						$new->areaEffects = clone $this->areaEffects;
						$new->hitEffects = clone $this->hitEffects;
						$new->setOwningEntity($this->player);
						$event->setProjectile($new);
					} else {
						$event->cancel();
					}
				}
			}
		}
	}

	protected function onActivate(): ActionResult {
		return ActionResult::SUCCEEDED_SILENT();
	}
}
