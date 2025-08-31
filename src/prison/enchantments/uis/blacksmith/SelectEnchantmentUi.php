<?php namespace prison\enchantments\uis\blacksmith;

use pocketmine\item\Durable;
use pocketmine\player\Player;

use prison\PrisonPlayer;
use prison\enchantments\ItemData;

use core\ui\elements\customForm\{
	Label,
	Dropdown
};
use core\ui\windows\CustomForm;
use core\utils\TextFormat;
use prison\enchantments\type\Enchantment;
use prison\item\UnboundTome;

class SelectEnchantmentUi extends CustomForm{

	public array $enchantments = [];

	public function __construct(
		public Durable $item,
		public UnboundTome $remover
	){
		parent::__construct("Which enchantment?");

		$this->addElement(new Label("Which enchantment would you like to remove from this item?"));

		$data = new ItemData($item);

		$dropdown = new Dropdown("Item selection");
		$key = 0;
		foreach($data->getEnchantments() as $ench){
			/** @var Enchantment $ench */
			$this->enchantments[$key] = $ench;
			$dropdown->addOption($ench->getName() . " " . $ench->getStoredLevel());
			$key++;
		}
		$this->addElement($dropdown);
	}

	public function close(Player $player) {
		/** @var PrisonPlayer $player */
		$player->showModal(new RemoveEnchantmentUi($player));
	}

	public function handle($response, Player $player) {
		/** @var PrisonPlayer $player */
		if(count($this->enchantments) < 1) return;

		$item = $this->item;
		$slot = $player->getInventory()->first($item, true);
		if($slot === -1){
			$player->sendMessage(TextFormat::RI . "This tool is no longer in your inventory!");
			return;
		}

		$remover = $this->remover;
		$slot = $player->getInventory()->first($remover, true);
		if($slot === -1){
			$player->sendMessage(TextFormat::RI . "This enchantment remover is no longer in your inventory!");
			return;
		}
		if($remover->getCost() > $player->getXpManager()->getXpLevel()){
			$player->sendMessage(TextFormat::RI . "You don't have enough XP levels to use this enchantment remover!");
			return;
		}
		
		$ench = $this->enchantments[$response[1]];

		$player->showModal(new ConfirmRemoveUi($item, $remover, $ench));
	}

}