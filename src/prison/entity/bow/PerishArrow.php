<?php namespace prison\entity\bow;

class PerishArrow extends EArrow{

	public function entityBaseTick(int $tickDiff = 1): bool{
		$hasUpdate = parent::entityBaseTick($tickDiff);

		if($this->onGround or $this->isCollided){
			$this->flagForDespawn();
			$hasUpdate = true;
		}

		return $hasUpdate;
	}

}