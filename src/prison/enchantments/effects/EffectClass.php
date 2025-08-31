<?php namespace prison\enchantments\effects;

use pocketmine\player\Player;
use pocketmine\world\Position;
use pocketmine\utils\TextFormat;

use prison\Prison;

class EffectClass{

	public $id;
	public $name;
	public $description;

	public $rarity;
	public $type;

	public $obtainable = true;

	public function __construct(int $id, string $name, string $description, int $rarity, int $type, bool $obtainable = true){
		$this->id = $id;
		$this->name = $name;
		$this->description = $description;

		$this->rarity = $rarity;
		$this->type = $type;

		$this->obtainable = $obtainable;
	}

	public function getId() : int{
		return $this->id;
	}

	public function getName() : string{
		return $this->name;
	}

	public function getDescription() : string{
		return $this->description;
	}

	public function getRarity() : int{
		return $this->rarity;
	}

	public function getRarityColor() : string{
		return EffectIds::RARITY_COLORS[$this->getRarity()];
	}

	public function getRarityTag() : string{
		return $this->getRarityColor() . TextFormat::BOLD . strtoupper(EffectIds::RARITY_TAGS[$this->getRarity()]);
	}

	public function getRarityName() : string{
		switch($this->getRarity()){
			case 1:
				return "Common";
			case 2:
				return "Uncommon";
			case 3:
				return "Rare";
			case 4:
				return "Legendary";
			case 5:
				return "Divine";
		}
		return "";
	}

	public function getType() : int{
		return $this->type;
	}

	public function getTypeName() : string{
		switch($this->getType()){
			default:
				return "Unknown";
			case EffectIds::TYPE_SWORD:
				return "Sword";
			case EffectIds::TYPE_TOOL:
				return "Tool";
		}
	}

	public function isObtainable() : bool{
		return $this->obtainable;
	}

	public function getCallable() : callable{
		return Prison::getInstance()->getEnchantments()->getEffects()->calls->calls[$this->getId()] ??
		function(Player $killer, $other){};
	}

}