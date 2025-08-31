<?php namespace prison\mysteryboxes;

use pocketmine\item\ItemFactory;
use pocketmine\math\Vector3;
use pocketmine\player\Player;

use prison\Prison;
use prison\mysteryboxes\commands\{
	AddKeys,
	KeyAll,
	ExtractKeys,
	KeyPack,
	SeeKeys
};
use prison\mysteryboxes\items\KeyNote;
use prison\mysteryboxes\pieces\{
	MysteryBox,
	ResendTextTask
};
use prison\mysteryboxes\pieces\tiers\{
	IronBox,
	GoldBox,
	DiamondBox,
	EmeraldBox,
	VoteBox,
	DivineBox
};

use core\Core;
use core\utils\SessionManager;

class MysteryBoxes{

	public array $boxes = [];

	public function __construct(public Prison $plugin){
		foreach (Structure::BOX_LOCATIONS as $i => $d) {
			if($d[3] == "iron"){
				$this->boxes[$i] = new IronBox($i);
				continue;
			}
			if($d[3] == "gold"){
				$this->boxes[$i] = new GoldBox($i);
				continue;
			}
			if($d[3] == "diamond"){
				$this->boxes[$i] = new DiamondBox($i);
				continue;
			}
			if($d[3] == "emerald"){
				$this->boxes[$i] = new EmeraldBox($i);
				continue;
			}
			if($d[3] == "vote"){
				$this->boxes[$i] = new VoteBox($i);
				continue;
			}
			if($d[3] == "divine"){
				$this->boxes[$i] = new DivineBox($i);
			}
		}

		$plugin->getServer()->getCommandMap()->registerAll("mysteryboxes", [
			new AddKeys($plugin, "addkeys", "Add MysteryBox keys"),
			new KeyAll($plugin, "keyall", "Give everyone keys!"),
			new ExtractKeys($plugin, "extractkeys", "Extract your keys into item form!"),
			new KeyPack($plugin, "keypack", "Give key packs (staff)"),
			new SeeKeys($plugin, "seekeys", "See how many keys you have")
		]);
	}

	public function close() : void{
		foreach($this->getBoxes() as $box){
			$box->despawnItems();
		}
	}

	public function getBoxes() : array{
		return $this->boxes;
	}

	public function getBoxById($id) : ?MysteryBox{
		return $this->boxes[$id] ?? null;
	}

	public function getBoxByPos(Vector3 $pos) : ?MysteryBox{
		foreach($this->getBoxes() as $id => $box){
			if($box->getVector3() == $pos) return $box;
		}
		return null;
	}

	public function isOpeningBox(Player $player) : bool{
		foreach($this->getBoxes() as $id => $box){
			if($box->getUsing() == $player) return true;
		}
		return false;
	}

	public function onJoin(Player $player) : void{
		foreach($this->getBoxes() as $box){
			$box->sendText($player);
		}
	}

	public function onLevelChange(Player $player) : void{
		foreach($this->getBoxes() as $box){
			$box->removeText($player);
			$this->plugin->getScheduler()->scheduleDelayedTask(new ResendTextTask($box, $player), 20);
		}
	}

}