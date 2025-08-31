<?php namespace prison\tags;

use pocketmine\utils\TextFormat;

class Tag{

	public $name;
	public $format;
	public $disabled;

	public function __construct(string $name, string $format = "", bool $disabled = false){
		$this->name = $name;
		$this->format = $format;
		$this->disabled = $disabled;
	}

	public function getName() : string{
		return $this->name;
	}

	public function getFormat() : string{
		if($this->format !== "") return TextFormat::BOLD . $this->format . " " . TextFormat::RESET;

		$name = $this->getName();
		if(strstr($name, "#") != false){
			return TextFormat::AQUA . "#" . TextFormat::BOLD . str_replace("#", "", $name) . " " . TextFormat::RESET;
		}
		return TextFormat::BOLD . TextFormat::AQUA . $name . " " . TextFormat::RESET;
	}

	public function isDisabled() : bool{
		return $this->disabled;
	}

	public function __toString(){
		return $this->getName();
	}

}