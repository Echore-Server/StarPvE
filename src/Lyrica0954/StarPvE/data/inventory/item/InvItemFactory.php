<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\data\inventory\item;

use Lyrica0954\StarPvE\data\inventory\item\artifact\SpeedBottleArtifact;
use pocketmine\utils\SingletonTrait;

class InvItemFactory {
    use SingletonTrait {
        getInstance as S_getInstance;
    }

    public static function getInstance(): self {
        return self::S_getInstance();
    }

    /**
     * @var InvItem[]
     */
    protected array $list;

    public function __construct() {
        $this->list = [];

        $this->register(new SpeedBottleArtifact(InvItemIds::SPEED_BOTTLE));
        $this->register(new InvApple(InvItemIds::APPLE));
    }

    public function register(InvItem $item, bool $override = false): void {
        if (isset($this->list[$item->getId()]) && !$override) {
            throw new \Exception("cannot override");
        }

        $this->list[$item->getId()] = $item;
    }

    public function get(int $id): ?InvItem {
        if (isset($this->list[$id])) {
            return clone $this->list[$id];
        } else {
            return null;
        }
    }

    public function getFromJson(array $json): ?InvItem {
        if (self::validateItemJson($json)) {
            $item = $this->get($json["id"]);
            if ($item instanceof InvItem) {
                $item->setCount($json["count"]);
                $item->setDisplayName($json["displayName"]);
                $item->setLore($json["lore"]);
                if (isset($json["entryItemIdentifier"])) {
                    $identifier = $json["entryItemIdentifier"];
                    # InvItem の継承クラス側で実装するから問題ない？
                }
            }

            return $item;
        } else {
            throw new \Exception("json is not valid");
        }
    }

    protected static function validateItemJson(array $json): bool {
        return (isset($json["id"]) &&
            isset($json["count"]) &&
            isset($json["displayName"]) &&
            isset($json["lore"])
        );
    }
}
