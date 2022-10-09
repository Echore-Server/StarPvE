<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\job\player\archer;

use Lyrica0954\StarPvE\data\condition\Condition;
use Lyrica0954\StarPvE\identity\IdentityGroup;
use Lyrica0954\StarPvE\job\Ability;
use Lyrica0954\StarPvE\job\AlwaysAbility;
use Lyrica0954\StarPvE\job\identity\ability\AbilitySignalIdentity;
use Lyrica0954\StarPvE\job\identity\ability\AbilityValueSignalIdentity;
use Lyrica0954\StarPvE\job\identity\ability\AttachAbilityIdentityBase;
use Lyrica0954\StarPvE\job\identity\ability\PercentageStatusIdentity;
use Lyrica0954\StarPvE\job\IdentitySpell;
use Lyrica0954\StarPvE\job\LineOption;
use Lyrica0954\StarPvE\job\player\AbilitySignal;
use Lyrica0954\StarPvE\job\player\archer\entity\ExplodeArrow;
use Lyrica0954\StarPvE\job\player\archer\entity\FreezeArrow;
use Lyrica0954\StarPvE\job\player\archer\entity\SpecialArrow;
use Lyrica0954\StarPvE\job\player\archer\entity\WoundArrow;
use Lyrica0954\StarPvE\job\player\archer\item\SpecialBow;
use Lyrica0954\StarPvE\job\player\PlayerJob;
use Lyrica0954\StarPvE\job\player\swordman\ForceFieldSkill;
use Lyrica0954\StarPvE\job\Skill;
use Lyrica0954\StarPvE\job\StatusTranslate;
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
		return new ArrowPartySkill($this);
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
			"§7- §l§9防衛[⚔]§r

弓矢を使って遠くから戦闘の支援や、敵の進行を妨害したりすることができる職業。
至近距離でなくても攻撃できるのが強み。";
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
		$f->register(ExplodeArrow::class, function (World $world, CompoundTag $nbt): ExplodeArrow {
			return new ExplodeArrow(EntityDataHelper::parseLocation($nbt, $world), null, $nbt->getByte(Arrow::TAG_CRIT, 0) === 1, $nbt);
		}, ['starpve:explode_arrow'], EntityLegacyIds::ARROW);

		$f->register(WoundArrow::class, function (World $world, CompoundTag $nbt): WoundArrow {
			return new WoundArrow(EntityDataHelper::parseLocation($nbt, $world), null, $nbt->getByte(Arrow::TAG_CRIT, 0) === 1, $nbt);
		}, ['starpve:wound_arrow'], EntityLegacyIds::ARROW);
	}

	public function onItemUse(Item $item) {
		if ($item->getId() === ItemIds::BOOK) {
			$activated = null;
			if ($this->player->isSneaking()) {
				$this->skill->activate();
				return;
			} else {
				#$result = $this->ability->activate();
				#$activated = $this->ability;
				$this->player->sendMessage("§cアビリティを発動するには矢を発射してください");
				return;
			}
		}
	}

	protected function init(): void {
		$this->defaultSpells = [
			(new IdentitySpell($this, "矢筒強化"))
				->addIdentity(new AbilityValueSignalIdentity(
					$this,
					null,
					AttachAbilityIdentityBase::ATTACH_ABILITY,
					SpecialBowAbility::SIGNAL_ARROW_MULTIPLY,
					2,
					"通常時の矢の本数"
				))
				->addIdentity(new PercentageStatusIdentity(
					$this,
					null,
					AttachAbilityIdentityBase::ATTACH_ABILITY,
					StatusTranslate::STATUS_DAMAGE,
					0.3
				)),
			(new IdentitySpell($this, "エンハンスドアロー"))
				->addIdentity(new AbilityValueSignalIdentity(
					$this,
					null,
					AttachAbilityIdentityBase::ATTACH_ABILITY,
					SpecialBowAbility::SIGNAL_ARROW_BOUNCE,
					1,
					"通常時の矢のバウンス回数"
				))
				->addIdentity(new AbilityValueSignalIdentity(
					$this,
					null,
					AttachAbilityIdentityBase::ATTACH_SKILL,
					ArrowPartySkill::SIGNAL_ARROW_BOUNCE,
					1,
					"矢のバウンス回数"
				))
		];
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
