<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\form;

use Lyrica0954\StarPvE\game\player\GamePlayer;
use Lyrica0954\StarPvE\game\shop\content\ShopContent;
use Lyrica0954\StarPvE\game\shop\Shop;
use Lyrica0954\StarPvE\utils\PlayerUtil;
use pocketmine\form\Form;
use pocketmine\player\Player;

class ShopForm implements Form{

    public function __construct(private Player $player, private Shop $shop){
        
    }

    public function jsonSerialize(): mixed{
        $buttons = [];
        foreach($this->shop->getContents() as $content){
            $text = "{$content->getName()}";
            if ($content->canBuy($this->player)){
                $text .= "\n§a購入可能";
            } else {
                $costItem = $content->getCost($this->player);
                $need = $costItem->getCount() - PlayerUtil::countItem($this->player, $costItem->getId());
                if ($need > 0){
                    $text .= "\n§c{$costItem->getName()}が不足しています §f/ §c{$need} 必要";
                } else {
                    $text .= "\n§cこのアイテムは購入できません";
                }
            }

            $buttons[] = [
                "text" => $text
            ];
        }

        return [
            "type"=>"form",
            "title"=>"ショップ >> ゲーム内 >> アイテム一覧",
            "content"=>"",
            "buttons"=>$buttons
        ];
    }

    public function handleResponse(Player $player, $data): void{
        if ($data !== null){
            $contents = array_values($this->shop->getContents());
            $pressedContent = $contents[$data] ?? null;
            if ($pressedContent instanceof ShopContent){
                $pressedContent->buy($player);
            }
        }
    }
}