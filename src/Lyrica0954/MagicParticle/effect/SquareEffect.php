<?php

declare(strict_types=1);

namespace Lyrica0954\MagicParticle\effect;

use Lyrica0954\MagicParticle\CircleParticle;
use Lyrica0954\MagicParticle\CoveredParticle;
use Lyrica0954\MagicParticle\LineParticle;
use Lyrica0954\MagicParticle\SingleParticle;
use Lyrica0954\StarPvE\utils\MathUtil;
use Lyrica0954\StarPvE\utils\VectorUtil;
use pocketmine\math\Vector2;
use pocketmine\math\Vector3;
use pocketmine\world\Position;

class SquareEffect extends ParticleEffect {

	private float $pitch;
	private float $yaw;

	public function __construct(
		private float $size,
		private int $linePpb
	)
	{
		$this->pitch = 0;
		$this->yaw = 0;
	}

	public function rotate(float $yaw, float $pitch){
		$this->yaw = VectorUtil::rotateYaw($this->yaw, $yaw);
		$this->pitch = VectorUtil::rotatePitch($this->pitch, $pitch);
	}

	public function setRotate(float $yaw, float $pitch){
		$this->yaw = $yaw;
		$this->pitch = $pitch;
	}

	public function draw(Position $pos): array{
		$particles = [];


		$pitch = VectorUtil::rotatePitch(-$this->pitch, 45);
		$npitch = VectorUtil::rotatePitch($this->pitch, 45);
		$yaw = VectorUtil::rotateYaw($this->yaw, 45);

		
		$rpitch = VectorUtil::rotatePitch(-$this->pitch, -45);
		$rnpitch = VectorUtil::rotatePitch($this->pitch, -45);
		$ryaw = VectorUtil::rotateYaw($this->yaw, -45);
		
		$threeYaw = VectorUtil::rotateYaw($yaw, 90);
		$rthreeYaw = VectorUtil::rotateYaw($ryaw, -90);

		$a = new Vector2($yaw, $pitch);
		$na = new Vector2($threeYaw, $npitch);
		$r = new Vector2($ryaw, $rpitch);
		$nr = new Vector2($rthreeYaw, $rnpitch);



		$pt = [
			#奥
			0 => new Vector2($a->x, $a->y), #右下
			1 => new Vector2($r->x, $a->y), #左下
			2 => new Vector2($a->x, $r->y), #右上
			3 => new Vector2($r->x, $r->y), #左上

			#手前
			4 => new Vector2($na->x, $na->y), #右下
			5 => new Vector2($nr->x, $na->y), #左下
			6 => new Vector2($na->x, $nr->y), #右上
			7 => new Vector2($nr->x, $nr->y), #左上
		];

		$connections = [
			0 => [
				new Vector2($r->x, $a->y), #が左下に
				new Vector2($a->x, $r->y) #が右上に
			],

			3 => [
				new Vector2($a->x, $r->y), #が右上に
				new Vector2($r->x, $a->y) #が左下に
			],

			

			4 => [
				new Vector2($nr->x, $na->y), #が左下に
				new Vector2($na->x, $nr->y), #が右上に

				new Vector2($a->x, $a->y) #が奥の右下に
			],

			5 => [
				new Vector2($r->x, $a->y) #が奥の左下に
			],

			6 => [
				new Vector2($a->x, $r->y) #が奥の右上に
			],

			7 => [
				new Vector2($na->x, $nr->y), #が右上に
				new Vector2($nr->x, $na->y), #が左下に

				new Vector2($r->x, $r->y) #が奥の左上に
			]
		];

		$fix = function(Vector3 $vec, Vector2 $angle) use($a, $na, $r, $nr){
			$rem = ($this->size * 0.4) / 2; #??????????????
			if ($angle->y == $a->y || $angle->y == $na->y){
				return $vec->add(0, $rem, 0);
			}

			if ($angle->y == $r->y || $angle->y == $nr->y){
				return $vec->subtract(0, $rem, 0);
			}

			return $vec;
		};

		foreach($pt as $o => $p){
			$vec = $this->getPoint($pos, $p->x, $p->y);
			
			$connection = $connections[$o] ?? null;
			if ($connection !== null){
				foreach($connection as $cp){
					if ($cp instanceof Vector2){
						$cvec = $this->getPoint($pos, $cp->x, $cp->y);
						$line = new LineParticle(VectorUtil::insertWorld($vec, $pos->getWorld()), $this->linePpb);
						$particles[] = new CoveredParticle($line, VectorUtil::insertWorld($cvec, $pos->getWorld()));
					}
				}
			}
		}

		return $particles;
	}

	protected function getPoint(Vector3 $v, float $yaw, float $pitch): Vector3{
		$dir = VectorUtil::getDirectionHorizontal($yaw);
		$dir->y = (VectorUtil::getDirectionVectorStrict(0, $pitch))->y;
		$result = $v->addVector($dir->multiply($this->size));
		return $result;
	}
}