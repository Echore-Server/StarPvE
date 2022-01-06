<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\utils;

use pocketmine\math\Vector3;

class VectorUtil {

    public static function getDirectionHorizontal(float $yaw){
        $x = -sin(deg2rad($yaw));
        $z = cos(deg2rad($yaw));
    
        $hor = new Vector3($x, 0, $z);
        return $hor->normalize();
    }
}