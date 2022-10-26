<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\job\player\shaman;

use Lyrica0954\MagicParticle\CircleParticle;
use Lyrica0954\MagicParticle\ParticleOption;
use Lyrica0954\MagicParticle\SingleParticle;
use Lyrica0954\StarPvE\data\condition\Condition;
use Lyrica0954\StarPvE\data\condition\FalseCondition;
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

攻撃がかなり強力だが
アビリティなどが特殊で扱いが難しく上級者向け。";
	}

	public function getAlAbilityName(): String {
		return "現実干渉";
	}

	public function getAlAbilityDescription(): String {
		return
			"常時 §d霊体召喚 §fスペルを所持";
	}

	protected function getInitialAbility(): Ability {
		return new AssaultAbility($this);
	}

	protected function getInitialSkill(): Skill {
		return new SpiritCrushSkill($this);
	}

	protected function getInitialIdentityGroup(): IdentityGroup {
		return new IdentityGroup();
	}

	public function getSelectableCondition(): ?Condition {
		return null;
	}


	protected function init(): void {
		$this->addSpell(new SpawnSpiritSpell($this));
	}
}
