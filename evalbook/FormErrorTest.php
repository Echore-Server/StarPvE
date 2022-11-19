<?php

use pjz9n\advancedform\custom\CallbackCustomForm;
use pjz9n\advancedform\custom\element\Slider;
use pjz9n\advancedform\custom\response\CustomFormResponse;
use pocketmine\player\Player;

/**
 * @var Player $_player
 */

$slider = new Slider("Slider", 0, 3, 2, null, "slider");
$form = CallbackCustomForm::create("Form", elements: [$slider], handleSubmit: function (Player $player, CustomFormResponse $response): void {
	$result = $response->getSliderResult("slider")->getInt();
	$player->sendMessage($result);
});
$_player->sendForm($form);
