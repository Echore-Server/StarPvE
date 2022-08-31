<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\service\message;

use Lyrica0954\Service\Service;
use Lyrica0954\StarPvE\job\player\PlayerJob;
use Lyrica0954\StarPvE\service\ListenerService;
use Lyrica0954\StarPvE\utils\Messanger;
use Lyrica0954\StarPvE\utils\TaskUtil;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\scheduler\TaskHandler;
use pocketmine\Server;

class TipMessageService extends Service {

	protected ?TaskHandler $task;

	/**
	 * @var string[]
	 */
	public array $list = [];

	protected int $index;

	protected function init(): void {
		$this->list = [
			"! をチャットの先頭に付けることでいつでもグローバルチャットにできます！",
			"サーバーの禁止事項や注意事項は読みましたか？",
			"今すぐDiscordに参加！ discord.gg/tvZ33RXFP5",
			"パーティー機能を使ってみんなでワイワイしよう！",
			"遊んでくれてありがとう！"
		];

		$this->index = 0;
	}

	protected function onEnable(): void {
		$this->task = TaskUtil::repeatingClosure(function () {
			if ((count($this->list) - 1) < $this->index) {
				$this->index = 0;
			}

			$tip = $this->list[$this->index];

			$this->index++;

			Server::getInstance()->broadcastMessage(Messanger::talk("§8Tip", "§7" . $tip), Server::getInstance()->getOnlinePlayers());
		}, (20 * 20));
	}

	protected function onDisable(): void {
		$this->task?->cancel();
	}
}
