<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\form;

use Lyrica0954\StarPvE\job\Spell;
use pocketmine\form\Form;
use pocketmine\player\Player;

class SpellListForm implements Form {

	/**
	 * @param Spell[] $spells
	 */
	public function __construct(protected array $spells) {
	}

	public function jsonSerialize(): mixed {
		$text = "";
		foreach ($this->spells as $spell) {
			$cooltime = round($spell->getCooltime() / 20, 1);
			$text .= "§7> §b{$spell->getName()}
§bクールタイム: §c{$cooltime}秒§f
{$spell->getDescription()}";
		}

		return [
			"type" => "form",
			"title" => "アビリティリスト",
			"content" => $text,
			"buttons" => []
		];
	}

	public function handleResponse(Player $player, $data): void {
	}
}
