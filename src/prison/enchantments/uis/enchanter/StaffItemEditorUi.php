<?php namespace prison\enchantments\uis\enchanter;

use pocketmine\data\bedrock\EnchantmentIdMap;
use pocketmine\item\{
	Durable
};
use pocketmine\player\Player;

use prison\Prison;
use prison\enchantments\ItemData;

use core\chat\Chat;
use core\ui\windows\CustomForm;
use core\ui\elements\customForm\{
	Label,
	Dropdown,
	Input
};
use core\utils\TextFormat;

class StaffItemEditorUi extends CustomForm{

	public array $items = [];

	public function __construct(Player $player){
		parent::__construct("Staff Item Editor");

		$this->addElement(new Label("Please enter the item information below!"));

		$dropdown = new Dropdown("Select Item");
		$key = 0;
		foreach($player->getInventory()->getContents() as $item){
			if($item instanceof Durable){
				$this->items[$key] = $item;
				$dropdown->addOption($item->getName() . TextFormat::RESET . TextFormat::WHITE . " (" . $item->getDamage() . " uses)");
				$key++;
			}
		}
		if(empty($this->items)){
			$dropdown->addOption("You have nothing to change!");
		}
		$this->addElement($dropdown);

		$this->addElement(new Input("New name", "blank for no change"));
		$this->addElement(new Input("New death tag", "blank for no change"));
		$this->addElement(new Input("Add enchantments", "kaboom 5, airstrike 4, etc"));
		$this->addElement(new Input("Take enchantments", "kaboom 5, airstrike 4, etc"));
		$this->addElement(new Input("Animation", "L"));
		$this->addElement(new Input("Set blocks mined", "blank for no change"));
		$this->addElement(new Input("Set kills", "blank for no change"));
		$this->addElement(new Input("Signed by (; to remove)", "blank for no change"));

		$this->addElement(new Label("Wow! That's a lot of item properties..."));
	}

	public function handle($response, Player $player){
		if(empty($this->items)){
			return;
		}
		$item = $this->items[$response[1]];
		if(($slot = $player->getInventory()->first($item, true)) == -1){
			$player->sendMessage(TextFormat::RED . "This item is no longer in your inventory! Hopefully you didn't spend a long time editing this item!");
			return;
		}

		$data = new ItemData($player->getInventory()->getItem($slot));

		$name = $response[2];
		if($name != "") $data->setCustomName(Chat::convertWithEmojis($name));

		$death = $response[3];
		if($death != "") $data->setDeathMessage($death);

		$add = $response[4];
		$aa = [];
		if($add !== ""){
			$ae = explode(",", $add);
			foreach($ae as $ee){
				$eee = explode(" ", $ee);
				if(empty($eee)) continue;

				$name = \str_replace('_', ' ', strtolower(array_shift($eee)));
				$level = (int) (array_shift($eee) ?? -1);
				if($level == 0) continue;

				$ench = Prison::getInstance()->getEnchantments()->getEnchantmentByName($name, true);
				if($ench === null) continue;

				$ench->setLevel(($level == -1 ? $ench->getMaxLevel() : $level), true);
				$aa[] = $ench;
			}
		}
		foreach($aa as $a){
			$data->addEnchantment($a->getEnchantment(), $a->getLevel());
		}

		$remove = $response[5];
		$ra = [];
		if($remove !== ""){
			$re = explode(",", $remove);
			foreach($re as $ee){
				$eee = explode(" ", $ee);
				if(empty($eee)) continue;

				$name = strtolower(array_shift($eee));
				$level = (int) (array_shift($eee) ?? -1);

				$ench = Prison::getInstance()->getEnchantments()->getEnchantmentByName($name, true);
				if($ench === null) continue;

				$ench->setLevel($level, true);
				$ra[] = $ench;
			}
		}
		foreach($ra as $a){
			if($data->getItem()->hasEnchantment(EnchantmentIdMap::getInstance()->fromId($a->getRuntimeId()))){
				$data->removeEnchantment($a->getRuntimeId(), $a->getLevel());
			}
		}

		$eff = $response[6];
		if($eff != ""){
			$effect = Prison::getInstance()->getEnchantments()->getEffects()->getEffectByName($eff);
			if($effect !== null){
				$data->setEffectId($effect->getId());
			}
		}

		$blocks = (int) $response[7];
		if($blocks > 0)
			$data->setBlocksMined($blocks);
		
		$kills = (int) $response[8];
		if($kills > 0)
			$data->setKills($kills);

		$signed = $response[9];
		if($signed != ""){
			if($signed == ";"){
				$data->unsign();
			}else{
				$data->sign($signed);
			}
		}

		$player->getInventory()->setItem($slot, $data->getItem());
		$player->sendMessage(TextFormat::GI . "Successfully edited item properties!");
	}

}