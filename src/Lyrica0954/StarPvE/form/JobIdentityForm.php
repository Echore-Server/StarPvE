<?php

declare(strict_types=1);


namespace Lyrica0954\StarPvE\form;

use Lyrica0954\StarPvE\data\condition\JobLevelCondition;
use Lyrica0954\StarPvE\form\help\HelpIdentityForm;
use Lyrica0954\StarPvE\identity\Identity;
use Lyrica0954\StarPvE\identity\player\PlayerArgIdentity;
use Lyrica0954\StarPvE\job\JobIdentity;
use Lyrica0954\StarPvE\job\player\PlayerJob;
use Lyrica0954\StarPvE\utils\Messanger;
use pocketmine\form\Form;
use pocketmine\player\Player;

class JobIdentityForm extends AdvancedForm {

	private array $identities = [];

	public function __construct(private Player $player, private PlayerJob $job, private array $defaults, private bool $sort = true) {
	}

	public function jsonSerialize(): mixed {

		$buttons = [];

		$buttons[] = ["text" => "特性とは？ §7(ヘルプに進みます)"];

		$activateableIdentity = [];
		$identityCount = count($this->defaults);
		$conditionMap = [];

		$configName = (new \ReflectionClass($this->job))->getShortName();

		foreach ($this->defaults as $identity) {
			$activateable = $identity->isApplicable();

			if ($identity instanceof PlayerArgIdentity || $identity instanceof JobIdentity) {
				$activateable = $identity->isApplicableFor($this->player);

				$cond = $identity->getCondition();
				if ($cond instanceof JobLevelCondition) {
					$conditionMap[$cond->min] ?? $conditionMap[$cond->min] = [];
					$conditionMap[$cond->min][] = $identity;
				}
			}

			if ($activateable) {
				$activateableIdentity[] = $identity;
			}

			$desc = $activateable ? "§a有効" : "§c無効";
			$fixed = FormUtil::fixText($identity->getDescription());
			if (!$this->sort) {
				$buttons[] = [
					"text" => "§l§6{$identity->getName()}\n§r{$desc} §f/ §7{$fixed}"
				];
				$this->identities[] = $identity;
			}
		}
		$activateableIdentityCount = count($activateableIdentity);

		if ($this->sort) {
			foreach ($conditionMap as $level => $identities) {
				/**
				 * @var (PlayerArgIdentity|JobIdentity)[] $identity
				 */

				$test = (new JobLevelCondition($level, $configName))->check($this->player);
				$desc = $test ? "§a" : "§c";
				$icount = count($identities);
				$buttons[] = [
					"text" => "{$desc}職業レベル {$level} で開放\n§7{$icount} 個の特性"
				];

				$this->identities[] = $identities;
			}
		}

		return [
			"type" => "form",
			"title" => "ショップ >> 職業 >> {$this->job->getName()} >> 特性",
			"content" => "現在 §b{$activateableIdentityCount}/{$identityCount} §fの特性が有効です！",
			"buttons" => $buttons
		];
	}

	public function handleResponse(Player $player, $data): void {
		parent::handleResponse($player, $data);

		if ($data !== null) {
			if ($data == 0) {
				$form = new HelpIdentityForm();
				$player->sendForm($form);
			} else {
				if ($this->sort) {
					$identities = $this->identities[$data - 1] ?? null;
					if ($identities !== null) {
						$form = new self($this->player, $this->job, $identities, false);
						$form->setChildForm($this);
						$player->sendForm($form);
					} else {
						Messanger::error($player, "Identity index error", Messanger::getIdFromObject($this, "handleResponse"));
					}
				} else {
					$identity = $this->identities[$data - 1] ?? null;
					if ($identity !== null) {
						$player->sendMessage(Messanger::talk("職業", "§cこの特性を有効するには以下の条件を満たす必要があります"));
						Messanger::condition($player, $identity->getCondition());
					} else {
						Messanger::error($player, "Identity index error", Messanger::getIdFromObject($this, "handleResponse"));
					}
				}
			}
		}
	}
}
