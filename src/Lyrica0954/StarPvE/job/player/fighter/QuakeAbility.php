<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\job\player\fighter;

use Lyrica0954\MagicParticle\LineParticle;
use Lyrica0954\MagicParticle\ParticleOption;
use Lyrica0954\MagicParticle\SingleParticle;
use Lyrica0954\StarPvE\game\wave\MonsterData;
use Lyrica0954\StarPvE\identity\Identity;
use Lyrica0954\StarPvE\job\Ability;
use Lyrica0954\StarPvE\job\AbilityStatus;
use Lyrica0954\StarPvE\job\ActionResult;
use Lyrica0954\StarPvE\StarPvE;
use Lyrica0954\StarPvE\translate\DescriptionTranslator;
use Lyrica0954\StarPvE\utils\EntityUtil;
use Lyrica0954\StarPvE\utils\ParticleUtil;
use Lyrica0954\StarPvE\utils\PlayerUtil;
use Lyrica0954\StarPvE\utils\TaskUtil;
use Lyrica0954\StarPvE\utils\VectorUtil;
use pocketmine\block\Block;
use pocketmine\block\BlockFactory;
use pocketmine\block\BlockLegacyIds;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\entity\Entity;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityRegainHealthEvent;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Vector3;
use pocketmine\scheduler\ClosureTask;
use pocketmine\scheduler\Task;
use pocketmine\scheduler\TaskHandler;
use pocketmine\world\particle\BlockBreakParticle;
use pocketmine\world\particle\Particle;

class QuakeAbility extends Ability {

	const SIGNAL_NO_DAMAGE = 0;

	public function getName(): string {
		return "クエイク";
	}

	public function getDescription(): string {
		$duration = DescriptionTranslator::second($this->duration);
		return
			sprintf('§b発動時:§f §c1♡ §fのダメージを受ける。
§d効果: §f%1$s 間、自身の能力が上昇する。', $duration);
	}

	protected function init(): void {
		$this->duration = new AbilityStatus(4.0 * 20);
		$this->cooltime = new AbilityStatus(12 * 20);
	}

	protected function onActivate(): ActionResult {
		if (!$this->signal->has(self::SIGNAL_NO_DAMAGE)) {
			$source = new EntityDamageEvent($this->player, EntityDamageEvent::CAUSE_MAGIC, 2);
			$this->player->attack($source);
		}

		$this->player->getEffects()->add(new EffectInstance(VanillaEffects::SPEED(), (int) $this->duration->get(), 2));
		$this->player->getEffects()->add(new EffectInstance(VanillaEffects::STRENGTH(), (int) $this->duration->get(), 1));

		return ActionResult::SUCCEEDED();
	}
}
