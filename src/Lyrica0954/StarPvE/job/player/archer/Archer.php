<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\job\player\archer;

use Lyrica0954\StarPvE\data\condition\Condition;
use Lyrica0954\StarPvE\identity\IdentityGroup;
use Lyrica0954\StarPvE\job\Ability;
use Lyrica0954\StarPvE\job\AlwaysAbility;
use Lyrica0954\StarPvE\job\LineOption;
use Lyrica0954\StarPvE\job\player\archer\entity\FreezeArrow;
use Lyrica0954\StarPvE\job\player\archer\entity\SpecialArrow;
use Lyrica0954\StarPvE\job\player\archer\item\SpecialBow;
use Lyrica0954\StarPvE\job\player\PlayerJob;
use Lyrica0954\StarPvE\job\player\swordman\ForceFieldSkill;
use Lyrica0954\StarPvE\job\Skill;
use Lyrica0954\StarPvE\utils\EntityUtil;
use Lyrica0954\StarPvE\utils\PlayerUtil;
use Lyrica0954\StarPvE\utils\TaskUtil;
use pocketmine\block\BlockToolType;
use pocketmine\data\bedrock\EntityLegacyIds;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\entity\EntityDataHelper;
use pocketmine\entity\EntityFactory;
use pocketmine\entity\projectile\Arrow;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerItemHeldEvent;
use pocketmine\item\Bow;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIdentifier;
use pocketmine\item\ItemIds;
use pocketmine\item\Sword;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\player\Player;
use pocketmine\scheduler\ClosureTask;
use pocketmine\scheduler\TaskHandler;
use pocketmine\Server;
use pocketmine\world\World;

class Archer extends PlayerJob implements Listener, AlwaysAbility {

	protected ?TaskHandler $task = null;

	protected int $lastSwordHeld = 0;
	protected ?TaskHandler $swordPrepareTask = null;

	protected function getInitialAbility(): Ability {
		return new SpecialBowAbility($this);
	}

	protected function getInitialSkill(): Skill {
		return new FreezeArrowSkill($this);
	}

	protected function getInitialIdentityGroup(): IdentityGroup {
		return new IdentityGroup();
	}

	public function getSelectableCondition(): ?Condition {
		return null;
	}

	public function close() {
		parent::close();

		$this->task?->cancel();
	}


	public function getName(): string {
		return "Archer";
	}

	public function getDescription(): string {
		return
			"§7- §l§a防衛[⚔]§r

弓矢を使って遠くから戦闘の支援や、敵の進行を妨害したりすることができる職業。
至近距離でなくても攻撃できるのが強み。
スキルのクールタイムがかなり長いため、使うタイミングに注意しよう。";
	}

	public function getAlAbilityName(): string {
		return "不得意";
	}

	public function getAlAbilityDescription(): string {
		return
			"剣のクールダウン §c+0.15秒§f
剣に持ち替えたとき、攻撃できるようになるまでに時間を要する。";
	}

	public function __construct(?Player $player) {
		parent::__construct($player);

		/**
		 * @var EntityFactory $f
		 */
		$f = EntityFactory::getInstance();
		$f->register(SpecialArrow::class, function (World $world, CompoundTag $nbt): SpecialArrow {
			return new SpecialArrow(EntityDataHelper::parseLocation($nbt, $world), null, $nbt->getByte(Arrow::TAG_CRIT, 0) === 1, $nbt);
		}, ['starpve:special_arrow'], EntityLegacyIds::ARROW);

		$f->register(FreezeArrow::class, function (World $world, CompoundTag $nbt): FreezeArrow {
			return new FreezeArrow(EntityDataHelper::parseLocation($nbt, $world), null, $nbt->getByte(Arrow::TAG_CRIT, 0) === 1, $nbt);
		}, ['starpve:freeze_arrow'], EntityLegacyIds::ARROW);
	}

	public function onItemUse(Item $item) {
		if ($item->getId() === ItemIds::BOOK) {
			$activated = null;
			if ($this->player->isSneaking()) {
				#$result = $this->skill->activate();
				#$activated = $this->skill;
				$this->player->sendMessage("§cスキルを発動するにはスニークをした状態で最大チャージで矢を発射してください");
				return;
			} else {
				#$result = $this->ability->activate();
				#$activated = $this->ability;
				$this->player->sendMessage("§cアビリティを発動するには矢を発射してください");
				return;
			}
		}
	}

	public function onEntityDamageByEntity(EntityDamageByEntityEvent $event) {
		$damager = $event->getDamager();
		$entity = $event->getEntity();

		if ($damager === $this->player && $damager instanceof Player) {
			$item = $damager->getInventory()->getItemInHand();
			if ($item instanceof Sword) {
				$elapsed = Server::getInstance()->getTick() - $this->lastSwordHeld;
				$event->setAttackCooldown($event->getAttackCooldown() + 3);
				if ($elapsed >= 20) {
					PlayerUtil::playSound($damager, "random.break", 2.0, 0.3);
				} else {
					$event->cancel();
					$damager->sendMessage("§c攻撃できません！あなたは剣を準備中です！");
				}
			}
		}
	}

	public function onHeldItem(PlayerItemHeldEvent $event) {
		$player = $event->getPlayer();
		$item = $event->getItem();
		if ($player === $this->player) {
			$this->swordPrepareTask?->cancel();
			if ($item instanceof Sword) {
				$this->lastSwordHeld = Server::getInstance()->getTick();

				$this->action->push(new LineOption("§d剣を準備中..."));

				$this->swordPrepareTask = TaskUtil::delayed(new ClosureTask(function () use ($player) {
					$this->action->push(new LineOption("§d剣の準備完了！"));
					PlayerUtil::playSound($player, "item.shield.block", 2.0, 0.4);
					$this->swordPrepareTask = null;
				}), 20);
			}
		}
	}
}
