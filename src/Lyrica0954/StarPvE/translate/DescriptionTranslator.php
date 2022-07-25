<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\translate;

use Lyrica0954\StarPvE\job\Ability;
use Lyrica0954\StarPvE\job\AbilityStatus;
use Lyrica0954\StarPvE\utils\EffectGroup;
use pocketmine\entity\effect\Effect;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\lang\KnownTranslationFactory;
use pocketmine\lang\Translatable;
use pocketmine\utils\TextFormat;

class DescriptionTranslator {

	public static function number(AbilityStatus $stat, string $d, string $resetter = TextFormat::WHITE): string {
		$num = $stat->getOriginal();
		$diff = $stat->getDiff();
		return TextFormat::RED . round($num, 2) . $d . self::diff($diff) . $resetter;
	}

	public static function diff(float $diff, int $point = 2): string {
		return (sprintf("§7(%+.{$point}f)", $diff));
	}

	public static function health(AbilityStatus $stat, bool $heart = true, string $resetter = TextFormat::WHITE): string {
		$health = $stat->getOriginal();
		$diff = $stat->getDiff();
		if ($heart) {
			$health /= 2;
			$diff /= 2;
		}
		return TextFormat::RED . round($health, 2) . ($heart ? "♡" : "") . self::diff($diff) . $resetter;
	}

	public static function effectGroup(EffectGroup $effectGroup, string $delimiter = ", ", string $resetter = TextFormat::WHITE): string {
		$t = "";
		foreach ($effectGroup->getAll() as $effect) {
			$t .= self::effect($effect, $resetter) . $delimiter;
		}
		if (count($effectGroup->getAll()) > 0) {
			return substr($t, 0, - (mb_strlen($delimiter, "UTF-8")));
		} else {
			return "";
		}
	}

	public static function second(AbilityStatus $stat, float $tps = 20.0, string $resetter = TextFormat::WHITE): string {
		$num = $stat->getOriginal() / $tps;
		$diff = $stat->getDiff() / $tps;
		return TextFormat::RED . round($num, 2) . "秒" . self::diff($diff) . $resetter;
	}

	public static function percentage(AbilityStatus $stat, bool $reverse = false, string $resetter = TextFormat::WHITE): string {
		$num = $stat->getOriginal();
		$diff = $stat->getDiff();
		if ($reverse) {
			$num = abs(1.0 - $num);
			$diff = -$diff;
		}
		return TextFormat::RED . round($num * 100, 1) . "%%" . self::diff($diff * 100, 1) . $resetter;
	}

	public static function effect(EffectInstance $instance, string $resetter = TextFormat::WHITE): string {
		$duration = round($instance->getDuration() / 20, 1);
		$amp = $instance->getAmplifier();
		$roma = ["I", "II", "III", "IV", "V", "VI", "VII", "VIII", "IX", "X"];
		$level = $roma[$amp] ?? ($instance->getEffectLevel());
		$name = $instance->getType()->getName();
		if ($name instanceof Translatable) {
			$name = SimpleTranslator::translate($name);
		}
		return TextFormat::GREEN . "{$name}{$level} ({$duration}秒)" . $resetter;
	}

	public static function job(string $name, string $resetter = TextFormat::WHITE): string {
		return TextFormat::LIGHT_PURPLE . $name . $resetter;
	}
}
