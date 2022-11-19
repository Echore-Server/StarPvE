<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\entity;

use Lyrica0954\MagicParticle\CoveredParticle;
use Lyrica0954\MagicParticle\effect\PartDelayedEffect;
use Lyrica0954\MagicParticle\effect\SaturatedLineworkEffect;
use Lyrica0954\MagicParticle\effect\SquareEffect;
use Lyrica0954\MagicParticle\PartDelayedParticle;
use Lyrica0954\MagicParticle\ParticleOption;
use Lyrica0954\MagicParticle\SingleParticle;
use Lyrica0954\MagicParticle\SphereParticle;
use Lyrica0954\MagicParticle\utils\MolangUtil;
use Lyrica0954\StarPvE\form\JobSelectForm;
use pocketmine\entity\Human;
use pocketmine\network\mcpe\protocol\EmotePacket;
use pocketmine\network\mcpe\protocol\MovePlayerPacket;

use Lyrica0954\StarPvE\PlayerController;
use Lyrica0954\StarPvE\StarPvE;
use Lyrica0954\StarPvE\utils\EmoteIds;
use Lyrica0954\StarPvE\utils\ParticleUtil;
use Lyrica0954\StarPvE\utils\RandomUtil;
use Lyrica0954\StarPvE\utils\TaskUtil;
use Lyrica0954\StarPvE\utils\VectorUtil;
use pocketmine\color\Color;
use pocketmine\entity\EntitySizeInfo;
use pocketmine\math\Vector2;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;
use pocketmine\player\Player;
use pocketmine\scheduler\TaskHandler;
use pocketmine\world\particle\Particle;
use pocketmine\world\Position;

class JobShop extends Human implements Ghost {

	protected $lookTick = 0;
	protected int $ptick = 0;
	protected static $emoted = [];

	protected Vector3 $plane;

	protected TaskHandler $particleTask;

	public function getName(): String {
		return "JobShop";
	}

	protected function getInitialSizeInfo(): EntitySizeInfo {
		return new EntitySizeInfo(1.8, 0.6);
	}

	public static function getNetworkTypeId(): string {
		return EntityIds::PLAYER;
	}

	public function onInteract(Player $player, Vector3 $clickPos): bool {
		$jobSelect = new JobSelectForm($player);
		$player->sendForm($jobSelect);
		return true;
	}

	protected function onDispose(): void {
		parent::onDispose();

		$this->particleTask->cancel();
	}

	protected function initEntity(CompoundTag $nbt): void {
		parent::initEntity($nbt);

		$this->plane = Vector3::zero();

		$std = new \stdClass;
		$std->color = new Color(0, 0, 0, 255);

		$this->particleTask = TaskUtil::repeatingClosure(function () use ($std) {

			$color = $std->color;
			/**
			 * @var Color $color
			 */



			ParticleUtil::send(
				new SingleParticle,
				$this->getWorld()->getPlayers(),
				Position::fromObject($this->getPosition()->add(0, 12, 0), $this->getWorld()),
				ParticleOption::spawnPacket("starpve:circle", MolangUtil::encode(ParticleUtil::circleMolang(2 * 0.05, 120, 5, new Color(1 * 255, (int)(0.4 * 255), (int)(0.4 * 255), (int)(0.5 * 255)), new Vector3(0, cos($this->plane->x) * 1, sin($this->plane->x) * 1))))
			);

			ParticleUtil::send(
				new SingleParticle,
				$this->getWorld()->getPlayers(),
				Position::fromObject($this->getPosition()->add(0, 12, 0), $this->getWorld()),
				ParticleOption::spawnPacket("starpve:circle", MolangUtil::encode(ParticleUtil::circleMolang(2 * 0.05, 120, 5, new Color((int)(0.4 * 255), (int)(0.4 * 255), (int)(1.0 * 255), (int)(0.5 * 255)), new Vector3(sin($this->plane->x) * 1, cos($this->plane->x) * 1, 0))))
			);

			ParticleUtil::send(
				new SingleParticle,
				$this->getWorld()->getPlayers(),
				Position::fromObject($this->getPosition()->add(0, 12, 0), $this->getWorld()),
				ParticleOption::spawnPacket("starpve:circle", MolangUtil::encode(ParticleUtil::circleMolang(2 * 0.05, 120, 5, new Color((int)(0.4 * 255), (int)(1.0 * 255), (int)(0.4 * 255), (int)(0.5 * 255)), new Vector3(sin($this->plane->x) * 1, cos($this->plane->x) * 1, sin($this->plane->z) * 1))))
			);

			ParticleUtil::send(
				new SingleParticle,
				$this->getWorld()->getPlayers(),
				Position::fromObject($this->getPosition()->add(0, 48, 0), $this->getWorld()),
				ParticleOption::spawnPacket("starpve:outwards_circle", MolangUtil::encode(
					ParticleUtil::motionCircleMolang(
						ParticleUtil::circleMolang(
							280 * 0.05,
							50,
							2,
							new Color((int) (((1 + sin($this->plane->x * 2)) / 2) * 255), 0, 0, 175),
							new Vector3(sin($this->plane->x) * 1, cos($this->plane->x) * 1, 0)
						),
						1.8,
						0.3,
						0.0
					)
				))
			);



			$this->plane->x += 0.075;
			$this->plane->z += 0.0375;
		}, 1);
	}

	public function entityBaseTick(int $tickDiff = 1): bool {
		$hasUpdate = parent::entityBaseTick($tickDiff);

		$this->lookTick += $tickDiff;
		if ($this->lookTick >= 6) {
			$this->lookTick = 0;


			#$molang = [];
			#$molang[] = MolangUtil::variable("dx", RandomUtil::rand_float(-1, 1));
			#$molang[] = MolangUtil::variable("dy", RandomUtil::rand_float(-1, 1));
			#$molang[] = MolangUtil::variable("dz", RandomUtil::rand_float(-1, 1));
			#$molang[] = MolangUtil::variable("speed", 3.0);
			#$molang[] = MolangUtil::variable("size", 1.25);
			#$molang[] = MolangUtil::variable("lifetime", 10.0);
			#$molang[] = MolangUtil::variable("hasCollision", 0.0);
			#$encoded = MolangUtil::encode($molang);
			#ParticleUtil::send(
			#	new SingleParticle,
			#	$this->getWorld()->getPlayers(),
			#	Position::fromObject($this->getEyePos(), $this->getWorld()),
			#	ParticleOption::spawnPacket("starpve:axe", $encoded)
			#);


			#$ef = new PartDelayedEffect((new SaturatedLineworkEffect(14, 3, 1, 5)), 2, 1, true);
			#ParticleUtil::send($ef, $this->getWorld()->getPlayers(), VectorUtil::keepAdd($this->getPosition(), 0, $this->getEyeHeight(), 0), ParticleOption::spawnPacket("minecraft:balloon_gas_particle", ""));


			#$this->sq->rotate(4, 0);
			#$this->sq->sendToPlayers($this->getWorld()->getPlayers(), VectorUtil::keepAdd($this->getPosition(), 0, 10, 0), ParticleOption::spawnPacket("starpve:soft_red_gas", ""));

			$nearestDist = PHP_INT_MAX;
			$nearestPlayer = null;
			foreach ($this->getWorld()->getPlayers() as $player) {
				$dist = $this->getPosition()->distance($player->getPosition());
				if ($dist < $nearestDist) {
					$nearestDist = $dist;
					$nearestPlayer = $player;
				}

				$gamePlayerManager = StarPvE::getInstance()->getGamePlayerManager();
				if (!in_array($player, self::$emoted, true) && ($gamePlayerManager->getGamePlayer($player) !== null)) {
					if ($dist <= 5.0) {
						$this->lookAt($player->getEyePos());
						$packet = EmotePacket::create($this->getId(), EmoteIds::WAVE, 1 << 0);
						$player->getNetworkSession()->sendDataPacket($packet);

						self::$emoted[] = $player;

						$par = new SingleParticle;
						ParticleUtil::send(
							$par,
							[$player],
							Position::fromObject($this->getEyePos()->add(0, 0.5, 0), $this->getWorld()),
							ParticleOption::spawnPacket("starpve:job_shop", "")
						);
					}
				}
			}

			if ($nearestPlayer !== null) {
				$this->lookAt($nearestPlayer->getEyePos());
			}
		}



		$this->ptick += $tickDiff;
		if (($this->ptick) >= 1) {
			$this->ptick = 0;


			#$ef = new PartDelayedParticle(new CoveredParticle(new SphereParticle(5, 6, 6), VectorUtil::keepAdd($this->getPosition(), 0, 9, 0)), 1, 12);
			#ParticleUtil::send($ef, $this->getWorld()->getPlayers(), option: ParticleOption::spawnPacket("starpve:soft_green_gas", ""));
			#
			#$ef = new PartDelayedParticle(new CoveredParticle(new SphereParticle(5, 6, 6), VectorUtil::keepAdd($this->getPosition(), 0, 9, 0)), 1, 12, true);
			#ParticleUtil::send($ef, $this->getWorld()->getPlayers(), option: ParticleOption::spawnPacket("starpve:soft_red_gas", ""));
		}

		return $hasUpdate;
	}
}
