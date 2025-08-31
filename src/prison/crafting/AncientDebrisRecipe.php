<?php

namespace prison\crafting;

use pocketmine\block\VanillaBlocks;
use pocketmine\crafting\ExactRecipeIngredient;
use pocketmine\crafting\FurnaceRecipe;
use pocketmine\item\VanillaItems;

final class AncientDebrisRecipe extends FurnaceRecipe {

	public function __construct() {
		parent::__construct(VanillaItems::NETHERITE_SCRAP()->setCount(2), new ExactRecipeIngredient(VanillaBlocks::ANCIENT_DEBRIS()->asItem()));
	}
}
