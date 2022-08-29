<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE;

use Lyrica0954\MagicParticle\CircleParticle;
use Lyrica0954\MagicParticle\ParticleOption;
use Lyrica0954\MagicParticle\SingleParticle;
use Lyrica0954\StarPvE\command\CommandLoader;
use Lyrica0954\StarPvE\data\player\adapter\GenericConfigAdapter;
use Lyrica0954\StarPvE\entity\Ghost;
use Lyrica0954\StarPvE\entity\JobShop;
use Lyrica0954\StarPvE\form\GameSelectForm;
use Lyrica0954\StarPvE\utils\ParticleUtil;
use Lyrica0954\StarPvE\utils\PlayerUtil;
use Lyrica0954\StarPvE\utils\TaskUtil;
use pocketmine\entity\Entity;
use pocketmine\entity\Location;
use pocketmine\entity\Skin;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityMotionEvent;
use pocketmine\event\HandlerList;
use pocketmine\event\HandlerListManager;
use pocketmine\event\inventory\InventoryTransactionEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerDropItemEvent;
use pocketmine\event\player\PlayerExhaustEvent;
use pocketmine\event\player\PlayerGameModeChangeEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerItemUseEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\event\server\DataPacketSendEvent;
use pocketmine\event\server\NetworkInterfaceRegisterEvent;
use pocketmine\inventory\ArmorInventory;
use pocketmine\inventory\Inventory;
use pocketmine\inventory\transaction\action\DropItemAction;
use pocketmine\inventory\transaction\InventoryTransaction;
use pocketmine\item\ItemIds;
use pocketmine\network\mcpe\raklib\RakLibInterface;
use pocketmine\permission\DefaultPermissionNames;
use pocketmine\player\GameMode;
use pocketmine\player\Player;
use pocketmine\world\Position;

class EventListener implements Listener {

	private StarPvE $plugin;

	public function __construct(StarPvE $plugin) {
		$this->plugin = $plugin;
		$this->plugin->getServer()->getPluginManager()->registerEvents($this, $plugin);
	}

	public function onItemUse(PlayerItemUseEvent $event) {
		$item = $event->getItem();
		$player = $event->getPlayer();

		$this->plugin->getJobManager()->onItemUse($event);

		if ($item->getId() === ItemIds::COMPASS) {
			$form = new GameSelectForm();
			$player->sendForm($form);
		} elseif ($item->getId() === ItemIds::REDSTONE_TORCH) {
			ParticleUtil::send(
				new SingleParticle,
				$player->getWorld()->getPlayers(),
				Position::fromObject($player->getEyePos(), $player->getWorld()),
				ParticleOption::spawnPacket("starpve:party_cracker", "")
			);
		}
	}

	public function onInteract(PlayerInteractEvent $event) {
		if ($event->getAction() === PlayerInteractEvent::RIGHT_CLICK_BLOCK) {
			$this->plugin->getJobManager()->onItemUse($event);
		}
	}

	public function onDamage(EntityDamageEvent $event) {
		$entity = $event->getEntity();

		if ($entity instanceof Ghost) {
			$event->cancel();
		}

		if ($entity instanceof Player) {

			if ($event->getCause() == EntityDamageEvent::CAUSE_FALL) {
				$event->cancel();
			}

			if ($entity->getWorld() === StarPvE::getInstance()->hub) {
				if ($entity->getLocation()->getY() < 0) {
					$event->cancel();

					PlayerUtil::teleportToLobby($entity);
				}
			}
		}

		if (!$event->isCancelled()) {
			$par = new SingleParticle;
			ParticleUtil::send(
				$par,
				$entity->getWorld()->getPlayers(),
				Position::fromObject($entity->getPosition()->add(0, $entity->size->getHeight() / 2, 0), $entity->getWorld()),
				ParticleOption::spawnPacket("starpve:entity_damage", "")
			);
		}
	}

	#public function onPacketSend(DataPacketSendEvent $event){
	#    $sessions = $event->getTargets();
	#    foreach($sessions as $session){
	#        if ($session->getPlayer()?->isOnline()){
	#            foreach($event->getPackets() as $pk){
	#                if (!$pk instanceof TextPacket){
	#                    $pkn = $pk->getName();
	#                    $session->getPlayer()->sendMessage("§c< §7{$pkn}");
	#                }
	#            }
	#        }
	#    }
	#}

	#public function onPacketReceive(DataPacketReceiveEvent $event) {
	#    $session = $event->getOrigin();
	#    $packet = $event->getPacket();
	#    $name = $packet->getName();

	#    $session->getLogger()->warning("Received Packet: {$name}");
	#}

	public function onExhaust(PlayerExhaustEvent $event) {
		$player = $event->getPlayer();

		if ($player->getWorld() === $this->plugin->hub) {
			$event->cancel();
		}
	}

	public function onMotion(EntityMotionEvent $event) {
		$entity = $event->getEntity();

		if ($entity instanceof Ghost) {
			$event->cancel();
		}
	}


	public function onItemDrop(PlayerDropItemEvent $event) {
		$player = $event->getPlayer();
		$item = $event->getItem();

		if (!$player->isCreative()) {
			if ($item->getId() !== ItemIds::EMERALD) {
				$event->cancel();
			}
		}
	}

	public function onInventoryTransaction(InventoryTransactionEvent $event) {
		$trans = $event->getTransaction();
		$player = $trans->getSource();
		$inventories = $trans->getInventories();

		if (!$player->isCreative()) {
			foreach ($inventories as $inventory) {
				if ($inventory instanceof ArmorInventory) {
					$event->cancel();
				}
			}
		}
	}

	public function onJoin(PlayerJoinEvent $event) {
		$player = $event->getPlayer();

		$event->setJoinMessage("§a> §7{$player->getName()}");

		#$pk = ChunkRadiusUpdatedPacket::create(10000);
		#$player->getNetworkSession()->sendDataPacket($pk);

		$player->sendTitle("§eStar PvE", "§6Development Server");
		PlayerUtil::reset($player);
		PlayerUtil::teleportToLobby($player);
		$player->setGamemode(GameMode::fromString("2"));
		$this->plugin->getGamePlayerManager()->addGamePlayer($player);

		if (StarPvE::getInstance()->hub === $player->getWorld()) {
			foreach ($player->getWorld()->getEntities() as $entity) {
				if ($entity instanceof JobShop) {
					$entity->close();
				}
			}

			$skinData = file_get_contents($this->plugin->getDataFolder() . "JobShopSkin.txt");
			$jobShop = new JobShop(new Location(2.5, 51, 6.5, $player->getWorld(), 0, 0), new Skin("Standard_Custom", $skinData));
			$jobShop->setNameTagVisible(true);
			$jobShop->setNameTagAlwaysVisible(true);
			$jobShop->setNameTag("§7------------------\n§d職業選択\n§7------------------");
			$jobShop->spawnToAll();
		}

		$adapter = GenericConfigAdapter::fetch($player);
		if ($adapter instanceof GenericConfigAdapter) {
			foreach ($adapter->getConfig()->get(GenericConfigAdapter::PERMS, []) as $perm) {
				$player->setBasePermission($perm, true);
			}
		}
	}

	public function onMove(\pocketmine\event\player\PlayerMoveEvent $event) {
		$player = $event->getPlayer();

		$pos = $player->getPosition()->floor();
		$block = $player->getWorld()->getBlockAt($pos->x, $pos->y, $pos->z);
		if ($block->getId() === 147) {
			$player->setMotion($player->getDirectionVector()->multiply(1.75));
		}

		if ($player->isCreative(true)) {
			$center = $player->getWorld()->getBlock($player->getPosition())->getPosition()->add(0.5, 0.0, 0.5);
			$player->sendActionBarMessage("§bあなたがいるブロックの中心\n§fx: §c{$center->x} §fy: §a{$center->y} §fz: §9{$center->z}");
		}
	}

	public function onPlayerQuit(\pocketmine\event\player\PlayerQuitEvent $event) {
		$player = $event->getPlayer();
		$gamePlayerManager = $this->plugin->getGamePlayerManager();
		$gamePlayerManager->getGamePlayer($player)?->leaveGame();
		$gamePlayerManager->removeGamePlayer($player);

		$jobManager = $this->plugin->getJobManager();
		$jobManager->setJob($player, null);


		$event->setQuitMessage("§c< §7{$player->getName()}");
	}

	public function onInterfaceRegister(NetworkInterfaceRegisterEvent $event) {
		$interface = $event->getInterface();

		if ($interface instanceof RakLibInterface) {
			$interface->setPacketLimit(PHP_INT_MAX);
		}
	}

	public function onGameModeChange(PlayerGameModeChangeEvent $event) {
		$player = $event->getPlayer();
		$newGamemode = $event->getNewGamemode();

		#$player->sendMessage("{$newGamemode->getEnglishName()}");

		if (!$player->hasPermission(DefaultPermissionNames::COMMAND_GAMEMODE) && $newGamemode->getEnglishName() === "Creative") {
			$event->cancel();
			$player->kick("インタラクション拒否");
		}
	}
}
