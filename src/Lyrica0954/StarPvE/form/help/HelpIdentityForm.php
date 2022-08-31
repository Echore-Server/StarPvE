<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\form\help;

use pocketmine\form\Form;
use pocketmine\player\Player;

class HelpIdentityForm implements Form {

	public function __construct() {
	}

	public function jsonSerialize(): mixed {
		return [
			"type" => "form",
			"title" => "ヘルプ >> 特性",
			"content" => "
§7> §b職業の特性
§f職業は特性というやりこむことで強くなるシステムがあり、
特性を解放すると攻撃力上昇、防御力上昇、アビリティのダメージ増加などの効果を受けられます。

ほとんどは職業のレベルアップで解放することができますが、
初めから持っている特性の中にはプレイヤーに負の効果を与えるものもあります。
",
			"buttons" => []
		];
	}

	public function handleResponse(Player $player, $data): void {
	}
}
