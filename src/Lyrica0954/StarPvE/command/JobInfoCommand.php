<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\command;

use Lyrica0954\StarPvE\form\JobInformationForm;
use Lyrica0954\StarPvE\form\StatusForm;
use Lyrica0954\StarPvE\game\player\GamePlayer;
use Lyrica0954\StarPvE\job\player\PlayerJob;
use Lyrica0954\StarPvE\StarPvE;
use Lyrica0954\StarPvE\utils\PlayerUtil;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

final class JobInfoCommand extends PluginCommandNoAuth {

	public function canRunBy(): int {
		return self::PLAYER;
	}

	protected function init(): void {
		$this->setAliases([
			"jobstatus",
			"jstat",
			"js",
		]);

		$this->setDescription("現在の職業ステータスの確認");
	}

	protected function run(CommandSender $sender, array $args): void {
		if ($sender instanceof Player) {
			$job = StarPvE::getInstance()->getJobManager()->getJob($sender);
			if ($job instanceof PlayerJob) {
				$form = new JobInformationForm($sender, $job);
				$sender->sendForm($form);
			} else {
				$sender->sendMessage("§cあなたは現在職業についていません！");
			}
		}
	}
}
