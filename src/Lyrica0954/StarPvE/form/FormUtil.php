<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\form;

class FormUtil {

	public static function dropdown(string $text, array $options, int $default = null): array {
		assert(isset($options[$default]));
		return ["type" => "dropdown", "text" => $text, "options" => $options, "default" => $default];
	}

	public static function input(string $text, string $placeholder = "", ?string $default = null): array {
		return ["type" => "input", "text" => $text, "placeholder" => $placeholder, "default" => $default];
	}

	public static function stepSlider(string $text, array $steps, int $default = -1): array {
		$json = ["type" => "step_slider", "text" => $text, "steps" => $steps];

		if ($default !== -1) {
			$json["default"] = $default;
		}

		return $json;
	}

	public static function slider(string $text, int $min, int $max, int $step = -1, int $default = -1): array {
		$json = ["type" => "slider", "text" => $text, "min" => $min, "max" => $max];

		if ($step !== -1) {
			$json["step"] = $step;
		}

		if ($default !== -1) {
			$json["default"] = $default;
		}

		return $json;
	}

	public static function toggle(string $text, bool $default = null): array {
		$json = ["type" => "toggle", "text" => $text];

		if ($default !== null) {
			$json["default"] = $default;
		}

		return $json;
	}
}
