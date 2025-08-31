<?php namespace prison\enchantments\uis\blacksmith;

use pocketmine\item\Durable;
use pocketmine\player\Player;

use prison\PrisonPlayer;

use core\ui\elements\customForm\{
	Label,
	Dropdown
};
use core\ui\windows\CustomForm;
use core\utils\TextFormat;
use prison\item\UnboundTome;

class RemoveEnchantmentUi extends CustomForm{

	public array $items = [];
	public array $removers = [];

	public function __construct(Player $player) {
		/** @var PrisonPlayer $player */
		parent::__construct("Remove enchantment");

		$this->addElement(new Label("Which item would you like to remove an enchantment from?"));

		$dropdown = new Dropdown("Item selection");
		$key = 0;
		foreach($player->getInventory()->getContents() as $item){
			if($item instanceof Durable && $item->hasEnchantments()){
				$this->items[$key] = $item;
				$dropdown->addOption($item->getName() . TextFormat::RESET . TextFormat::WHITE . " (" . count($item->getEnchantments()) . " enchantments)");
				$key++;
			}
		}
		$this->addElement($dropdown);

		$this->addElement(new Label("Which unbound tome would you like to use?"));
		$dropdown = new Dropdown("Unbound Tome");
		$key = 0;
		foreach($player->getInventory()->getContents() as $item){
			if($item instanceof UnboundTome){
				$this->removers[$key] = $item;
				$dropdown->addOption("Return: " . $item->getReturnChance() . "%% - XP: " . $item->getCost());
				$key++;
			}
		}
		$this->addElement($dropdown);
	}

	public function handle($response, Player $player) {
		/** @var PrisonPlayer $player */
		if(count($this->items) < 1) return;
		if(count($this->removers) < 1) return;

		$item = $this->items[$response[1]];
		$slot = $player->getInventory()->first($item, true);
		if($slot === -1){
			$player->sendMessage(TextFormat::RI . "This tool is no longer in your inventory!");
			return;
		}
		
		$remover = $this->removers[$response[3]];
		$slot = $player->getInventory()->first($remover, true);
		if($slot === -1){
			$player->sendMessage(TextFormat::RI . "This unbound tome is no longer in your inventory!");
			return;
		}
		if($remover->getCost() > $player->getXpManager()->getXpLevel()){
			$player->sendMessage(TextFormat::RI . "You don't have enough XP levels to use this unbound tome!");
			return;
		}

		$player->showModal(new SelectEnchantmentUi($item, $remover));
	}

}