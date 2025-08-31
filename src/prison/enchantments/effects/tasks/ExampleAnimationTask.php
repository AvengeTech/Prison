<?php namespace prison\enchantments\effects\tasks;

class ExampleAnimationTask extends AnimationTask{

	public function onRun() : void{
		$timer = $this->getTimer();
		if($this->isNew()){
			echo "Animation Started...", PHP_EOL;
		}else{
			switch($timer){
				case 10:
					echo "Animation Variation - Ticks: ";
					break;
				default:
					echo "Normal Animation Run - Ticks: ";
					break;
			}
			echo $timer, PHP_EOL;
		}

		if($this->isLastCall()){
			echo "Last task fire!", PHP_EOL;
		}

		parent::onRun();
	}

}