<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\form;

use Lyrica0954\StarPvE\job\AbilitySpell;
use Lyrica0954\StarPvE\job\IdentitySpell;
use Lyrica0954\StarPvE\job\Spell;
use pocketmine\form\Form;
use pocketmine\player\Player;

class SpellInformationForm extends AdvancedForm {

	public function __construct(protected Spell $spell) {
	}


	public function jsonSerialize(): mixed {
		$text = "";
		if ($this->spell instanceof AbilitySpell) {
			$cooltime = round($this->spell->getCooltime()->get() / 20, 1);
			$text = "§b{$this->spell->getName()} §d(アビリティ)
§bクールタイム: §c{$cooltime}秒§f
{$this->spell->getDescription()}";
		} elseif ($this->spell instanceof IdentitySpell) {
			$text = "§b{$this->spell->getName()} §d(ノーマル)\n";
			foreach ($this->spell->getIdentityGroup()->getAll() as $identity) {
				$fixed = FormUtil::fixText($identity->getDescription());
				$text .= "§c[{$identity->getName()}] §f{$fixed}§r\n";
			}
		}

		return [
			"type" => "form",
			"title" => "スペルリスト >> {$this->spell->getName()}",
			"content" => $text,
			"buttons" => []
		];
	}

	public function handleResponse(Player $player, $data): void {
		parent::handleResponse($player, $data);
	}
}
