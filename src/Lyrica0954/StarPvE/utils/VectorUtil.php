<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\utils;

use pocketmine\math\Facing;
use pocketmine\math\Vector2;
use pocketmine\math\Vector3;
use pocketmine\world\Position;
use pocketmine\world\World;
class VectorUtil {

    public static function getDirectionHorizontal(float $yaw){
        $x = -sin(deg2rad($yaw));
        $z = cos(deg2rad($yaw));
    
        $hor = new Vector3($x, 0, $z);
        return $hor->normalize();
    }

    public static function getDirection(int $facing): Vector3{
        switch($facing){
            case Facing::DOWN:
                return new Vector3(0, -1, 0);
            case Facing::UP:
                return new Vector3(0, 1, 0);
            case Facing::NORTH:
                return new Vector3(0, 0, 1);
            case Facing::SOUTH:
                return new Vector3(0, 0, -1);
            case Facing::WEST:
                return new Vector3(1, 0, 0);
            case Facing::EAST:
                return new Vector3(-1, 0, 0);
            default:
                return new Vector3(0, 0, 0);

        }
    }

    public static function reverseAngle(float $yaw = 0, float $pitch = 0): Vector2{
        #pitch = 45
        $angle = new Vector2($yaw, $pitch);
        $angle->y = -$angle->y;

        $angle->x = $angle->x + 180;
        if ($angle->x > 360){
            $angle->x -= 360;
        }

        return $angle;
    }

    public static function rotatePitch(float $pitch, float $deg): float{
        $pitch += $deg;
        if ($pitch > 90){
			$pitch -= 180;
		}
		if ($pitch < -90){
			$pitch += 180;
		}

        return $pitch;
    }

    public static function rotateYaw(float $yaw, float $deg): float{
		$yaw += $deg;
		if ($yaw > 360){
			$yaw -= 360;
		}

        return $yaw;
	}

    public static function getNearestSpherePosition(Vector3 $vec, Vector3 $center, float $size): Vector3{
        $angle = self::getAngle($center, $vec);
        $dir = self::getDirectionVector($angle->x, $angle->y);
        $dir->x = -$dir->x;
        $dir->z = -$dir->z;
        $nearest = $center->addVector($dir->multiply($size));
        return $nearest;
    }


    public static function reAdd(Vector3 $vec, float $add): Vector3{
        $new = clone $vec;
        if ($new->x > 0){
            $new->x += $add;
        }

        if ($new->y > 0){
            $new->y += $add;
        }

        if ($new->z > 0){
            $new->z += $add;
        }

        return $new;
    }

    public static function getDirectionVector(float $yaw, float $pitch){
        $y = -sin(deg2rad($pitch));
		$xz = cos(deg2rad($pitch));
		$x = -$xz * sin(deg2rad($yaw));
		$z = $xz * cos(deg2rad($yaw));

        return (new Vector3($x, $y, $z))->normalize();
    }

    public static function getDirectionVectorStrict(float $yaw, float $pitch){
        $y = -sin(deg2rad($pitch));
        $x = sin(deg2rad($yaw));
        $z = cos(deg2rad($yaw));

        return (new Vector3($x, $y, $z))->normalize();
    }

    public static function keepAdd(Vector3 $t, float $x, float $y, float $z): Vector3{
        $v = clone $t;
        $v->x += $x;
        $v->y += $y;
        $v->z += $z;
        return $v;
    }

    public static function getAngleRelative(Vector3 $base, Vector3 $relative, float $yaw): Vector3{ #相対座標
        #mcbeのコマンドの座標指定 ^ と同じ

        #relative->x = 左右方向への移動数
        #relative->y = (相対座標 y~) と同じ
        #relative->z = 前後方向への移動数

        $az = self::getDirectionHorizontal($yaw);
        $ax = self::getDirectionHorizontal($yaw + 90);

        $x = $ax->multiply($relative->x);
        $y = new Vector3(0, $relative->y, 0);
        $z = $az->multiply($relative->z);

        $final = $base->addVector($x)->addVector($y)->addVector($z);
        return $final;
    }

    public static function getAngle(Vector3 $from, Vector3 $to, float $eyeHeight = 0): Vector2{
        $horizontal = sqrt(($to->x - $from->x) ** 2 + ($to->z - $from->z) ** 2);
		$vertical = $to->y - ($from->y + $eyeHeight);
		$pitch = -atan2($vertical, $horizontal) / M_PI * 180; //negative is up, positive is down

		$xDist = $from->x - $to->x;
		$zDist = $from->z - $to->z;

		$yaw = atan2($zDist, $xDist) / M_PI * 180 - 90;
		if($yaw < 0){
			$yaw += 360.0;
		}

        return new Vector2($yaw, $pitch);
    }

    public static function to3D(Vector2 $vec2, float $y = 0.0): Vector3{
        return new Vector3($vec2->x, $y, $vec2->y);
    }

    public static function insertWorld(Vector3 $vec, World $world): Position{
        return new Position($vec->x, $vec->y, $vec->z, $world);
    }
}