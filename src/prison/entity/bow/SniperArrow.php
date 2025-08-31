<?php namespace prison\entity\bow;

class SniperArrow extends EArrow{

	public $gravity = 0.01;

	protected function getInitialGravity(): float
	{
		return $this->gravity;
	}

}