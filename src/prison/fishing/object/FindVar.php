<?php namespace prison\fishing\object;

class FindVar{

	public function __construct(
		public string $type,
		public int $amount,
		public array $extra = []
	){}

	public function getType() : string{
		return $this->type;
	}

	public function getAmount() : int{
		return $this->amount;
	}

	public function getExtra() : array{
		return $this->extra;
	}

}