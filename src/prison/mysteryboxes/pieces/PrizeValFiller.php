<?php namespace prison\mysteryboxes\pieces;

class PrizeValFiller{

	/** @var string */
	public $name;
	/** @var string */
	public $value;
	/** @var string */
	public $extra;

	public function __construct(string $name, ?string $value = ""){
		$this->name = $name;
		$this->value = $value ?? "";
	}

	public function getName() : string{
		return $this->name;
	}

	public function getValue() : string{
		return $this->value;
	}

	public function setValue(string $value = "") : void{
		$this->value = $value;
	}

	public function getExtra() : string{
		return $this->extra;
	}

	public function setExtra(string $extra = "") : void{
		$this->extra = $extra;
	}

}