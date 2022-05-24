<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\command;

use pocketmine\command\utils\InvalidCommandSyntaxException;
use pocketmine\command\{CommandSender, CommandExecutor, Command};
use pocketmine\plugin\Plugin;
use pocketmine\plugin\PluginOwned;
use pocketmine\plugin\PluginOwnedTrait;
use pocketmine\console\ConsoleCommandSender;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;

abstract class PluginCommandNoAuth extends Command implements PluginOwned {
    use PluginOwnedTrait;

    private $executor;

    const CONSOLE = 1;
    const PLAYER = 2;

    const MAP = [
        self::CONSOLE => "Console",
        self::PLAYER => "Player",
        (self::PLAYER | self::CONSOLE) => "Console/Player"
    ];

    public static function senderTypeText(int $type) {
        return self::MAP[$type];
    }

    public function __construct(string $name, PluginBase $owner, CommandExecutor $executor) {
        parent::__construct($name);
        $this->owningPlugin = $owner;
        $this->executor = $executor;
        $this->usageMessage = "";
        $this->init();

        $owner->getServer()->getCommandMap()->register($name, $this, $name);
    }

    protected function init(): void {
    }

    abstract public function canRunBy(): int;

    public function onInvalidSender(CommandSender $sender, int $customSenderType = 0): void {
        $senderType = ($customSenderType > 0 ? $customSenderType : $this->canRunBy());
        $text = self::senderTypeText($senderType);
        $sender->sendMessage("§cこのコマンドは ({$text}) のみ実行できます。");
    }

    abstract protected function run(CommandSender $sender, array $args): void;

    protected function testSender(CommandSender $sender, int $customSenderType = 0): bool {
        $rb = $customSenderType > 0 ? $customSenderType : $this->canRunBy();
        $invalidSender = false;
        if ($rb !== (self::PLAYER | self::CONSOLE)) {
            if ($rb === self::PLAYER && $sender instanceof ConsoleCommandSender) $invalidSender = true;
            if ($rb === self::CONSOLE && $sender instanceof Player) $invalidSender = true;
        } else {
            if (!$sender instanceof ConsoleCommandSender && !$sender instanceof Player) {
                $invalidSender = true;
            }
        }

        if ($invalidSender) {
            $this->onInvalidSender($sender, $rb);
        }

        return !$invalidSender;
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args) {

        if (!$this->owningPlugin->isEnabled()) {
            return false;
        }

        if (!$this->testPermission($sender)) {
            return false;
        }

        if (!$this->testSender($sender)) {
            return false;
        }

        $this->run($sender, $args);
        return true;

        #$success = $this->executor->onCommand($sender, $this, $commandLabel, $args);
        #
        #if(!$success and $this->usageMessage !== ""){
        #    throw new InvalidCommandSyntaxException();
        #}
        #
        #return $success;
    }

    public function getExecutor(): CommandExecutor {
        return $this->executor;
    }

    public function setExecutor(CommandExecutor $executor): void {
        $this->executor = $executor;
    }
}
