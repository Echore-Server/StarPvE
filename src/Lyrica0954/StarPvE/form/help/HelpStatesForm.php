<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\form\help;

use Lyrica0954\StarPvE\form\AdvancedForm;
use pocketmine\form\Form;
use pocketmine\player\Player;

class HelpStatesForm extends AdvancedForm {

	public function __construct() {
	}

	public function jsonSerialize(): mixed {
		return [
			"type" => "form",
			"title" => "ヘルプ >> 状態異常",
			"content" => "
§7> §d帯電
この状態の敵に攻撃すると、ほかの敵にもダメージを与えることができる。

§7> §d致命傷
この状態の敵に対しての物理ダメージが一定倍率上昇する。
",
			"buttons" => []
		];
	}

	public function handleResponse(Player $player, $data): void {
	}
}
