<?php namespace prison\quests;

use pocketmine\block\BlockTypeIds;

use prison\Prison;
use prison\PrisonPlayer;
use prison\quests\commands\QuestCmd;
use prison\quests\Structure as QuestIds;

use prison\quests\shop\PointShop;

use core\Core;
use core\utils\conversion\LegacyItemIds;
use core\utils\ItemRegistry;
use pocketmine\item\ItemTypeIds;

class Quests{
	
	public PointShop $shop;

	public array $quests = [];

	public array $random = [];

	public function __construct(public Prison $plugin){
		$this->shop = new PointShop($plugin);

		$plugin->getServer()->getCommandMap()->register("quest", new QuestCmd($plugin, "quest", "Open your Quest menu"));

		$this->setup();
	}

	public function setup() : void{
		QuestIds::setup();
		foreach(QuestIds::QUEST_DATA as $id => $data){
			$this->quests[$id] = new Quest($id, $data["name"], $data["level"], $data["rank"], $data["messages"], isset(QuestIds::$QUEST_TAKES[$id]) ? QuestIds::$QUEST_TAKES[$id] : [], $data["startingprogress"]);
		}
	}

	public function getShop() : PointShop{
		return $this->shop;
	}

	public function getQuests() : array{
		return $this->quests;
	}

	public function getQuest($id) : ?Quest{
		return $this->quests[$id] ?? null;
	}

	public function getClonedQuest($id) : ?Quest{
		foreach($this->getQuests() as $qid => $quest){
			if($id == $qid) return clone $quest;
		}
		return null;
	}

	public function getRandomQuest(string $mine = "") : ?Quest{
		if($mine == ""){
			$id = mt_rand(0, count($this->quests) - 1);
		}else{
			$ids = [];
			if($mine == "free") $mine = "z";
			$mine = ord(strtoupper($mine)) - ord("A") + 1;
			foreach($this->getQuests() as $quest){
				$rq = ord(strtoupper($quest->getRequiredRank())) - ord("A") + 1;
				if($rq <= $mine) $ids[] = $quest->getId();
			}
			$id = $ids[mt_rand(0, count($ids) - 1)];
		}
		return $this->getClonedQuest($id);
	}

	public function tick() : void{
		Core::getInstance()->getEntities()->getFloatingText()->getText("questmaster")->update();
		foreach($this->plugin->getServer()->getOnlinePlayers() as $player){
			/** @var PrisonPlayer $player */
			if(!$player->hasGameSession()) continue;
			$session = $player->getGameSession()->getQuests();
			if(!$session->hasActiveQuest()) continue;

			$quest = $session->getCurrentQuest();
			switch($quest->getId()){
				case QuestIds::SAMMY_SHEEP:
					$count = 0;
					foreach (QuestIds::$QUEST_TAKES[QuestIds::SAMMY_SHEEP] as $i) {
						if ($player->getInventory()->contains($i)) $count++;
					}
					if($quest->isComplete()){
						if($count < 16){
							$quest->setComplete(false, $player);
						}
					}else{
						if($count == 16){
							$quest->setComplete(true, $player);
						}
					}
					$quest->progress["clay colors"][0] = $count;
				break;
				case QuestIds::QUAZARK:
					$cobble = 0;
					$wood = 0;
					foreach($player->getInventory()->getContents() as $item){
						if($item->getTypeId() == -BlockTypeIds::COBBLESTONE){
							$cobble += $item->getCount();
							continue;
						}
						if ($item->getTypeId() == -BlockTypeIds::OAK_PLANKS) {
							$wood += $item->getCount();
						}
					}
					if($cobble > 256) $cobble = 256;
					if($wood > 256) $wood = 256;
					if($quest->isComplete()){
						if($cobble < 256 || $wood < 256){
							$quest->setComplete(false, $player);
						}
					}else{
						if($cobble == 256 && $wood == 256){
							$quest->setComplete(true, $player);
						}
					}
					$quest->progress["cobblestone"][0] = $cobble;
					$quest->progress["oak planks"][0] = $wood;
				break;
				case QuestIds::BLADE:
					$swords = 0;
					foreach($player->getInventory()->getContents() as $item){
						if ($item->getTypeId() == ItemTypeIds::IRON_SWORD) {
							$swords++;
							if($swords == 10) break;
						}
					}
					if($quest->isComplete()){
						if($swords != 10){
							$quest->setComplete(false, $player);
						}
					}else{
						if($swords == 10){
							$quest->setComplete(true, $player);
						}
					}
					$quest->progress["iron swords"][0] = $swords;
				break;
				case QuestIds::FLYING_DUTCHMAN:
					$planks = 0;
					foreach($player->getInventory()->getContents() as $item){
						if($planks >= 64) break;
						if ($item->getTypeId() == -BlockTypeIds::OAK_PLANKS) {
							$planks += $item->getCount();
						}
					}
					if($planks > 64) $planks = 64;
					if($quest->isComplete()){
						if($planks != 64){
							$quest->setComplete(false, $player);
						}
					}else{
						if($planks == 64){
							$quest->setComplete(true, $player);
						}
					}
					$quest->progress["oak planks"][0] = $planks;
				break;
				default:
				break;
			}
		}
	}

}