<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\command;

use Lyrica0954\StarPvE\form\JobInformationForm;
use Lyrica0954\StarPvE\StarPvE;
use NeiroNetwork\VanillaCommands\parameter\Parameter;
use pocketmine\permission\DefaultPermissionNames;
use pocketmine\permission\DefaultPermissions;
use pocketmine\permission\Permission;
use pocketmine\permission\PermissionManager;
use pocketmine\Server;

class CommandLoader {

	public static function load(StarPvE $p) {
		$cmd = $p->getServer()->getCommandMap()->getCommand("op");
		$cmd?->setPermission(DefaultPermissions::ROOT_CONSOLE);

		$cmd = $p->getServer()->getCommandMap()->getCommand("deop");
		$cmd?->setPermission(DefaultPermissions::ROOT_CONSOLE);

		$cmd = $p->getServer()->getCommandMap()->getCommand("kill");
		$cmd?->setPermission(DefaultPermissions::ROOT_OPERATOR);

		new HubCommand("hub", $p, $p);
		new GameCommand("game", $p, $p);
		new JobInfoCommand("jobstats", $p, $p);
		new TestCommand("testf", $p, $p);
		new PlayerStatusCommand("stats", $p, $p);
		new SettingCommand("setting", $p, $p);
		new PartyCommand("party", $p, $p);
		new WarnCommand("warn", $p, $p);

		new TaskInfoCommand("taskinfo", $p, $p);
	}

	public static function registerPermissions(): void {
		$console = PermissionManager::getInstance()->getPermission(DefaultPermissions::ROOT_CONSOLE);
		$operator = PermissionManager::getInstance()->getPermission(DefaultPermissionNames::GROUP_OPERATOR);
		$operator->addChild(PermissionNames::COMMAND_WARN, true);

		$owner = (new Permission(PermissionNames::OWNER, "StarPvE Owner", [
			DefaultPermissions::ROOT_OPERATOR => true,
			PermissionNames::TRUSTED => true
		]));
		DefaultPermissions::registerPermission($owner);

		$trusted = (new Permission(PermissionNames::TRUSTED, "StarPvE Trusted Admin", [
			PermissionNames::ADMIN => true,
			DefaultPermissionNames::COMMAND_EFFECT => true,
			DefaultPermissionNames::COMMAND_ENCHANT => true,
			DefaultPermissionNames::COMMAND_GIVE => true,
			DefaultPermissionNames::COMMAND_ME => true,
			DefaultPermissionNames::COMMAND_SAY => true,
			DefaultPermissionNames::COMMAND_CLEAR_OTHER => true,
			DefaultPermissionNames::COMMAND_KILL_OTHER => true,
		]));
		DefaultPermissions::registerPermission($trusted);

		$admin = (new Permission(PermissionNames::ADMIN, "StarPvE Admin", [
			PermissionNames::BUILDER => true,
			PermissionNames::COMMAND_WARN => true,
			DefaultPermissionNames::COMMAND_STOP => true,
			DefaultPermissionNames::COMMAND_KICK => true,
			DefaultPermissionNames::COMMAND_KILL_SELF => true,
			DefaultPermissionNames::COMMAND_WHITELIST_LIST => true
		]));
		DefaultPermissions::registerPermission($admin);

		$team = (new Permission(PermissionNames::TEAM, "StarPvE Team", [
			DefaultPermissionNames::BROADCAST_ADMIN => true,
			DefaultPermissionNames::BROADCAST_USER => true,
			DefaultPermissionNames::COMMAND_CLEAR_SELF => true,
			DefaultPermissionNames::COMMAND_STATUS => true,
			DefaultPermissionNames::COMMAND_TELEPORT => true,
			"vanillacommands.all" => true
		]));
		DefaultPermissions::registerPermission($team);

		$builder = (new Permission(PermissionNames::BUILDER, "StarPvE Builder", [
			PermissionNames::TEAM => true,
			"buildertools.command" => true,
			"multiworld.command" => true,
			"multiworld.command.manage" => true,
			"multiworld.command.create" => true,
			"multiworld.command.debug" => true,
			"multiworld.command.delete" => false,
			"multiworld.command.duplicate" => true,
			"multiworld.command.help" => true,
			"multiworld.command.info" => true,
			"multiworld.command.list" => true,
			"multiworld.command.load" => true,
			"multiworld.command.teleport" => true,
			"multiworld.command.unload" => true,
			"buildertools.command.biome" => true,
			"buildertools.command.blockinfo" => true,
			"buildertools.command.center" => true,
			"buildertools.command.clearinventory" => true,
			"buildertools.command.copy" => true,
			"buildertools.command.cube" => true,
			"buildertools.command.cut" => true,
			"buildertools.command.cylinder" => true,
			"buildertools.command.decoration" => true,
			"buildertools.command.draw" => true,
			"buildertools.command.fill" => true,
			"buildertools.command.pos1" => true,
			"buildertools.command.flip" => true,
			"buildertools.command.help" => true,
			"buildertools.command.hcube" => true,
			"buildertools.command.hcylinder" => true,
			"buildertools.command.hsphere" => true,
			"buildertools.command.id" => true,
			"buildertools.command.island" => true,
			"buildertools.command.merge" => true,
			"buildertools.command.move" => true,
			"buildertools.command.naturalize" => true,
			"buildertools.command.outline" => true,
			"buildertools.command.paste" => true,
			"buildertools.command.pyramid" => true,
			"buildertools.command.redo" => true,
			"buildertools.command.replace" => true,
			"buildertools.command.rotate" => true,
			"buildertools.command.schematic" => true,
			"buildertools.command.pos2" => true,
			"buildertools.command.sphere" => true,
			"buildertools.command.stack" => true,
			"buildertools.command.tree" => true,
			"buildertools.command.undo" => true,
			"buildertools.command.walls" => true,
			"buildertools.command.wand" => true,
			DefaultPermissionNames::COMMAND_GAMEMODE => true,
		]));
		DefaultPermissions::registerPermission($builder);

		$commandWarn = new Permission(PermissionNames::COMMAND_WARN, "StarPvE Command Access: warn");
		DefaultPermissions::registerPermission($commandWarn);
	}
}
