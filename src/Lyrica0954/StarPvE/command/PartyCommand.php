<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\command;

use Lyrica0954\StarPvE\form\JobInformationForm;
use Lyrica0954\StarPvE\form\StatusForm;
use Lyrica0954\StarPvE\game\player\GamePlayer;
use Lyrica0954\StarPvE\job\player\PlayerJob;
use Lyrica0954\StarPvE\player\party\Party;
use Lyrica0954\StarPvE\player\party\PartyCreationOption;
use Lyrica0954\StarPvE\player\party\PartyInvite;
use Lyrica0954\StarPvE\player\party\PartyManager;
use Lyrica0954\StarPvE\StarPvE;
use Lyrica0954\StarPvE\utils\PlayerUtil;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\Server;

final class PartyCommand extends PluginCommandNoAuth {

	public function canRunBy(): int {
		return self::PLAYER;
	}

	protected function init(): void {
		$this->setAliases([
			"p",
		]);

		$this->setDescription("パーティー");
	}

	protected function run(CommandSender $sender, array $args): void {
		if ($sender instanceof Player) {
			$sub = $args[0] ?? "";
			switch ($sub) {
				case "create":
					$success = PartyManager::getInstance()->create(new PartyCreationOption($sender, []));
					if ($success) {
						$sender->sendMessage("§dParty §7>> §aパーティーを作成しました！ §7(/party invite <プレイヤー名> でプレイヤーを招待しましょう！)");
					} else {
						$sender->sendMessage("§dParty §7>> §cパーティーを作成できませんでした");
					}
					break;
				case "disband":
					$party = PartyManager::getInstance()->get($sender);
					if ($party instanceof Party) {
						$success = PartyManager::getInstance()->disband($party, $sender);
						if ($success) {
							$sender->sendMessage("§dParty §7>> §aパーティーを解散しました！");
						} else {
							$sender->sendMessage("§dParty §7>> §cパーティーを解散できませんでした");
						}
					} else {
						$sender->sendMessage("§dParty §7>> §cパーティーに所属していません");
					}
					break;
				case "leave":
					PartyManager::getInstance()->leaveCurrent($sender);
					$sender->sendMessage("§dParty §7>> §aパーティーを去りました");
					break;
				case "invite":
					$victimName = $args[1] ?? "";
					$victim = Server::getInstance()->getPlayerByPrefix($victimName);
					if ($victim instanceof Player) {
						$party = PartyManager::getInstance()->get($sender);
						if ($party instanceof Party) {
							$invite = $party->publishInvite($sender, $victim);
							if ($invite instanceof PartyInvite) {
								$success = PartyManager::getInstance()->publish($invite);
								if ($success) {
									$sender->sendMessage("§dParty §7>> §a招待を {$victim->getName()} に送信しました。2分間有効です。");
								} else {
									$sender->sendMessage("§dParty §7>> §c招待を {$victim->getName()} に送信できませんでした");
								}
							} else {
								$sender->sendMessage("§dParty §7>> §c招待を {$victim->getName()} 向けに発行できませんでした");
							}
						} else {
							$sender->sendMessage("§dParty §7>> §cパーティーに所属していません");
						}
					} else {
						$sender->sendMessage("§dParty §7>> §c{$victimName} が見つかりません");
					}
					break;
				case "accept":
					$inviterName = $args[1] ?? "";
					$inviter = Server::getInstance()->getPlayerByPrefix($inviterName);
					if ($inviter instanceof Player) {
						PartyManager::getInstance()->acceptInvite($sender, $inviter);
					} else {
						$sender->sendMessage("§dParty §7>> §c{$inviterName} が見つかりません");
					}
					break;
				case "info":
					$party = PartyManager::getInstance()->get($sender);
					if ($party instanceof Party) {
						$host = $party->getHost();
						$list = implode(", ", array_map(function (Player $player) {
							return $player->getName();
						}, $party->getPlayers()));
						$invites = array_map(function (PartyInvite $invite) {
							return "§a{$invite->getInviter()->getName()} §7--> §c{$invite->getVictim()->getName()}";
						}, $party->getActiveInvites());
						$invitesList = implode(",\n", $invites);
						$inviteCount = count($party->getPublishedInvites());
						$sender->sendMessage("§dParty §7>> §fパーティー情報\n§bホスト: §f{$host->getName()}\n§bプレイヤー: §f{$list}\n§b発行した招待: {$inviteCount}\n有効な招待:\n{$invitesList}");
					}
					break;
			}
		}
	}
}
