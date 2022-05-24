<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\job\player;

use Lyrica0954\StarPvE\identity\IdentityGroup;
use Lyrica0954\StarPvE\job\Ability;
use Lyrica0954\StarPvE\job\ActionBarManager;
use Lyrica0954\StarPvE\job\ActionListManager;
use Lyrica0954\StarPvE\job\Skill;
use Lyrica0954\StarPvE\job\cooltime\CooltimeAttachable;
use Lyrica0954\StarPvE\job\cooltime\CooltimeHandler;
use Lyrica0954\StarPvE\job\cooltime\CooltimeNotifier;
use Lyrica0954\StarPvE\job\Job;
use Lyrica0954\StarPvE\job\JobIdentityGroup;
use Lyrica0954\StarPvE\job\LineOption;
use Lyrica0954\StarPvE\StarPvE;
use Lyrica0954\StarPvE\utils\TaskUtil;
use pocketmine\entity\Skin;
use pocketmine\event\HandlerListManager;
use pocketmine\event\Listener;
use pocketmine\item\Item;
use pocketmine\item\ItemIds;
use pocketmine\player\Player;
use pocketmine\scheduler\TaskHandler;
use pocketmine\Server;

abstract class PlayerJob extends Job {

    protected ?Player $player = null;

    protected Ability $ability;
    protected Skill $skill;

    protected IdentityGroup $identityGroup;

    protected CooltimeNotifier $cooltimeNotifier;

    protected ActionListManager $action;
    protected int $lastActionUpdate;

    protected ?TaskHandler $actionTask;

    public function __construct(?Player $player = null) {

        if ($player instanceof Player) { #JobManager への登録を簡単にするため
            $this->player = $player;

            $this->log("§dCreated for {$player->getName()}");

            if ($this instanceof Listener) Server::getInstance()->getPluginManager()->registerEvents($this, StarPvE::getInstance());
        } else {
            $this->player = null;

            #$this->log("§dCreated for none");
        }
        $this->ability = $this->getInitialAbility();
        $this->skill = $this->getInitialSkill();
        $this->identityGroup = $this->getInitialIdentityGroup();
        $this->identityGroup->apply($player);
        $this->action = new ActionListManager();
        $this->lastActionUpdate = 0;
        if ($player instanceof Player) {
            $this->cooltimeNotifier = new CooltimeNotifier($player);
            $this->cooltimeNotifier->addCooltimeHandler($this->ability->getCooltimeHandler());
            $this->cooltimeNotifier->addCooltimeHandler($this->skill->getCooltimeHandler());
            $this->cooltimeNotifier->start();

            $this->actionTask = TaskUtil::repeatingClosure(function () use ($player) {
                if ($player instanceof Player) {
                    $changed = $this->action->hasChanged();
                    $this->action->update(1);
                    #print_r($this->action->getSorted());
                    if ($changed || Server::getInstance()->getTick() - $this->lastActionUpdate >= 40) {
                        $this->lastActionUpdate = Server::getInstance()->getTick();

                        $player->sendTip($this->action->getText());
                    }

                    #$player->sendMessage($this->action->hasChanged() ? "true" : "false");
                }
            }, 1);
        }
    }

    public function close() {
        $this->ability->close();
        $this->skill->close();
        $this->cooltimeNotifier->stop();

        $this->identityGroup->reset($this->player);

        $this->identityGroup->close();

        $this->player = null;
        $this->actionTask?->cancel();
        $this->log("§dClosed");

        if ($this instanceof Listener) HandlerListManager::global()->unregisterAll($this);
    }

    public function onItemUse(Item $item) {
        if ($item->getId() === ItemIds::BOOK) {
            $activated = null;
            if ($this->player->isSneaking()) {
                $result = $this->skill->activate();
                $activated = $this->skill;
            } else {
                $result = $this->ability->activate();
                $activated = $this->ability;
            }

            $name = $activated->getCooltimeHandler()->getId();
            #$this->log("Activated {$name}");
            if ($result->isFailedByCooltime()) {
                $this->action->push(new LineOption("§c現在{$name}はクールタイム中です！"));
            } elseif ($result->isFailedAlreadyActive()) {
                $this->action->push(new LineOption("§c{$name}は既にアクティブです！"));
            } elseif ($result->isSucceeded()) {
                $this->action->push(new LineOption("§a{$name}を発動しました！"));
            } elseif ($result->isFailed()) {
                $this->action->push(new LineOption("§c{$name}を発動できません！"));
            } elseif ($result->isAbandoned()) {
                #bomb!
            }
        }
    }

    public function getPlayer(): ?Player {
        return $this->player;
    }

    public function getCooltimeNotifier(): CooltimeNotifier {
        return $this->cooltimeNotifier;
    }

    public function getActionListManager(): ActionListManager {
        return $this->action;
    }

    public function getAbility(): Ability {
        return $this->ability;
    }

    public function getSkill(): Skill {
        return $this->skill;
    }

    public function setAbility(Ability $ability): void {
        $this->cooltimeNotifier->removeCooltimeHandler($this->ability->getCooltimeHandler());
        $this->ability->close();
        $this->ability = $ability;
        $this->cooltimeNotifier->addCooltimeHandler($ability->getCooltimeHandler());
    }

    public function setSkill(Skill $skill): void {
        $this->cooltimeNotifier->removeCooltimeHandler($this->skill->getCooltimeHandler());
        $this->skill->close();
        $this->skill = $skill;
        $this->cooltimeNotifier->addCooltimeHandler($skill->getCooltimeHandler());
    }

    public function getIdentityGroup(): IdentityGroup {
        return $this->identityGroup;
    }

    abstract protected function getInitialAbility(): Ability;

    abstract protected function getInitialSkill(): Skill;

    abstract protected function getInitialIdentityGroup(): IdentityGroup;

    public function canActivateAbility(): bool {
        return !$this->ability->getCooltimeHandler()->isActive();
    }

    public function canActivateSkill(): bool {
        return !$this->skill->getCooltimeHandler()->isActive();
    }

    public function log(string $message) {
        StarPvE::getInstance()->log("§7[PlayerJob - {$this->getName()}] {$message}");
    }
}
