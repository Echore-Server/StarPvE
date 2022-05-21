<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\job\player\tank;

use Lyrica0954\StarPvE\data\condition\Condition;
use Lyrica0954\StarPvE\game\wave\MonsterData;
use Lyrica0954\StarPvE\identity\Identity;
use Lyrica0954\StarPvE\identity\IdentityGroup;
use Lyrica0954\StarPvE\identity\player\AddMaxHealthIdentity;
use Lyrica0954\StarPvE\job\Ability;
use Lyrica0954\StarPvE\job\AlwaysAbility;
use Lyrica0954\StarPvE\job\identity\ability\AddBaseAreaIdentity;
use Lyrica0954\StarPvE\job\identity\ability\AddBaseDamageIdentity;
use Lyrica0954\StarPvE\job\identity\ability\AttachAbilityIdentityBase;
use Lyrica0954\StarPvE\job\identity\ability\IncreaseDamageIdentity;
use Lyrica0954\StarPvE\job\JobIdentityGroup;
use Lyrica0954\StarPvE\job\LineOption;
use Lyrica0954\StarPvE\job\player\PlayerJob;
use Lyrica0954\StarPvE\job\player\swordman\ForceFieldSkill;
use Lyrica0954\StarPvE\job\player\swordman\LeapAbility;
use Lyrica0954\StarPvE\job\Skill;
use Lyrica0954\StarPvE\translate\DescriptionTranslator;
use Lyrica0954\StarPvE\utils\EntityUtil;
use Lyrica0954\StarPvE\utils\PlayerUtil;
use ParentIterator;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\entity\Entity;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\Listener;
use pocketmine\network\mcpe\protocol\types\ParticleIds;
use pocketmine\player\Player;

class Tank extends PlayerJob implements AlwaysAbility, Listener {

	protected float $energy = 0.0;

	const ENERGY_FORMAT = '§eエネルギー§f: §e%1$d';

	protected LineOption $actionLine;

	protected function getInitialAbility(): Ability {
		return new RegrowthAbility($this);
	}

	protected function getInitialSkill(): Skill {
		return new EnergyFieldSkill($this);
	}

	protected function getInitialIdentityGroup(): IdentityGroup {
		$g = new IdentityGroup();
		$list = [
			Identity::setCondition(new AddMaxHealthIdentity(10), null)
		];
		$g->addAll($list);
		return $g;
	}

	public function __construct(?Player $player) {
		parent::__construct($player);

		$this->energy = 0.0;
		$this->actionLine = LineOption::immobile(sprintf(self::ENERGY_FORMAT, $this->energy), -2);
		$this->action->setLine($this->action->getMax(), $this->actionLine);
	}

	public function getName(): string {
		return "Tank";
	}

	public function getDescription(): string {
		$pulseAbility = new EnergyPulseAbility($this);
		$ct = round($pulseAbility->getCooltime() / 20, 1);
		return
			"§7- §l§a支援§r

耐久力が高く、防御能力が多いタンク。耐久力をいかして前衛で敵の攻撃を受け止めたり、敵のヘイトを受けるのが得意な職業。

§l特殊アビリティ§r §7- §d{$pulseAbility->getName()}§f
§bクールタイム: §c{$ct}秒§f
{$pulseAbility->getDescription()}
";
	}

	public function getAlAbilityName(): string {
		return "蓄積";
	}

	public function getAlAbilityDescription(): string {
		return
			"ダメージを受けると、受けたダメージの量(防具や能力によるダメージ軽減、ダメージ増加は考慮しない)エネルギーが蓄積する。
スキルの発動中は蓄積しない。

スキルの発動中、エネルギーの蓄積量によっていろいろな追加能力が発動する。

・§c200§f 以上の場合
§c0.5秒§f 毎にフィールド内のランダムな敵 §c1体§f に防具貫通の §c1♡§f ダメージを与える攻撃を行う。

・§c300§f 以上の場合
エネルギーフィールドの範囲が §c1.5倍§f になる。

・§c400§f 以上の場合
特殊アビリティに防具貫通 §c4♡§f ダメージを与える攻撃を追加するが、
消費エネルギーが §c2倍§f になる。
";
	}

	public function getSelectableCondition(): ?Condition {
		return null;
	}

	public function onEntityDamage(EntityDamageEvent $event) {
		$entity = $event->getEntity();
		if ($entity === $this->player) {
			if ($entity instanceof Player) {
				if (!$this->getSkill()->isActive()) {
					$this->addEnergy($event->getOriginalBaseDamage());
				}
			}
		}
	}

	public function getEnergy(): float {
		return $this->energy;
	}

	public function setEnergy(float $energy): void {
		$this->energy = max(0, $energy);
		$this->actionLine->setText(sprintf(self::ENERGY_FORMAT, $this->energy));
	}

	public function addEnergy(float $energy): void {
		$this->setEnergy($this->getEnergy() + $energy);
	}
}
