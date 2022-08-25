<?php

declare(strict_types=1);

namespace Lyrica0954\StarPvE\game\shop;

use Lyrica0954\StarPvE\game\shop\content\ShopContent;

class Shop {

	/**
	 * @var ShopContent[]
	 */
	private array $contents;

	public function __construct() {
		$this->contents = [];
	}

	public function addContent(ShopContent $content): void {
		$this->contents[spl_object_hash($content)] = $content;
	}


	/**
	 * @return ShopContent[]
	 */
	public function getContents(): array {
		return $this->contents;
	}

	public function removeContent(ShopContent $content): void {
		unset($this->contents[spl_object_hash($content)]);
	}
}
