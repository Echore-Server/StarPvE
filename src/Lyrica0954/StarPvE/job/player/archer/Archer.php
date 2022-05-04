<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\job\player\archer;

use Lyrica0954\StarPvE\data\condition\Condition;
use Lyrica0954\StarPvE\identity\IdentityGroup;
use Lyrica0954\StarPvE\job\Ability;

use Lyrica0954\StarPvE\job\player\archer\entity\FreezeArrow;
use Lyrica0954\StarPvE\job\player\archer\entity\SpecialArrow;
use Lyrica0954\StarPvE\job\player\archer\item\SpecialBow;
use Lyrica0954\StarPvE\job\player\PlayerJob;
use Lyrica0954\StarPvE\job\player\swordman\ForceFieldSkill;
use Lyrica0954\StarPvE\job\Skill;
use Lyrica0954\StarPvE\utils\TaskUtil;
use pocketmine\block\BlockToolType;
use pocketmine\data\bedrock\EntityLegacyIds;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\entity\EntityDataHelper;
use pocketmine\entity\EntityFactory;
use pocketmine\entity\projectile\Arrow;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerItemHeldEvent;
use pocketmine\item\Bow;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIdentifier;
use pocketmine\item\ItemIds;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\player\Player;
use pocketmine\scheduler\TaskHandler;
use pocketmine\world\World;

class Archer extends PlayerJob implements Listener{

	protected ?TaskHandler $task = null;

	protected function getInitialAbility(): Ability{
		return new SpecialBowAbility($this);
	}

	protected function getInitialSkill(): Skill{
		return new FreezeArrowSkill($this);
	}

	protected function getInitialIdentityGroup(): IdentityGroup{
		return new IdentityGroup();
	}

	public function getSelectableCondition(): ?Condition{
		return null;
	}

	public function close(){
		parent::close();

		$this->task?->cancel();
	}
	

	public function getName(): string{
		return "Archer";
	}

    public function getDescription(): string{
        return 
"§7- §l§a防衛[⚔]§r

弓矢を使って遠くから戦闘の支援や、敵の進行を妨害したりすることができる職業。
至近距離でなくても攻撃できるのが強み。
スキルのクールタイムがすごく長いため、使うタイミングに注意しよう。";
    }

	public function __construct(?Player $player){
		parent::__construct($player);

		/**
		 * @var EntityFactory $f
		 */
		$f = EntityFactory::getInstance();
        $f->register(SpecialArrow::class, function(World $world, CompoundTag $nbt) : SpecialArrow{
            return new SpecialArrow(EntityDataHelper::parseLocation($nbt, $world), null, $nbt->getByte(Arrow::TAG_CRIT, 0) === 1, $nbt);
        }, ['starpve:special_arrow'], EntityLegacyIds::ARROW);

        $f->register(FreezeArrow::class, function(World $world, CompoundTag $nbt) : FreezeArrow{
            return new FreezeArrow(EntityDataHelper::parseLocation($nbt, $world), null, $nbt->getByte(Arrow::TAG_CRIT, 0) === 1, $nbt);
        }, ['starpve:freeze_arrow'], EntityLegacyIds::ARROW);
	}

	public function onItemUse(Item $item){
        if ($item->getId() === ItemIds::BOOK){
            $activated = null;
            if ($this->player->isSneaking()){
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
}