<?php namespace prison\enchantments\uis\blacksmith;

use pocketmine\item\Durable;
use pocketmine\player\Player;

use prison\PrisonPlayer;
use prison\enchantments\ItemData;
use prison\item\Nametag;

use core\chat\Chat;
use core\ui\windows\CustomForm;
use core\ui\elements\customForm\{
	Label,
	Dropdown,
	Input
};
use core\utils\ItemRegistry;
use core\utils\TextFormat;

class RenameItemUi extends CustomForm{

	public $items = [];

	public function __construct(Player $player) {
		/** @var PrisonPlayer $player */
		parent::__construct("Rename Item");

		$this->addElement(new Label("What item would you like to rename?"));

		$dropdown = new Dropdown("Item selection");
		$key = 0;
		foreach($player->getInventory()->getContents() as $item){
			if($item instanceof Durable){
				$this->items[$key] = $item;
				$dropdown->addOption($item->getName() . TextFormat::RESET . TextFormat::WHITE . ($item->hasEnchantments() ? " (" . count($item->getEnchantments()) . " enchantments)" : ""));
				$key++;
			}
		}
		$this->addElement($dropdown);

		$this->addElement(new Label("What would you like to rename this item?"));
		$this->addElement(new Input("Item name", "cool sword"));
	}

	public function handle($response, Player $player) {
		/** @var PrisonPlayer $player */
		if(empty($this->items)) return;

		$nt = ItemRegistry::NAMETAG();
		$nt->init();
		$nt = $player->getInventory()->first($nt);
		if($nt == -1){
			$player->sendMessage(TextFormat::RN . "Your inventory must contain a " . TextFormat::AQUA . "Nametag" . TextFormat::GRAY . " to do this!");
			return;
		}

		$item = $this->items[$response[1]];
		$data = new ItemData($item);
		if(!$data->canEdit()){
			$player->sendMessage(TextFormat::RN . "This item cannot be edited!");
			return;
		}

		$text = $response[3];
		$mbl = mb_strlen($text);
		$mbl += substr_count($text, TextFormat::ESCAPE);
		if($mbl != strlen($text) && !$player->hasRank()){
			$player->sendMessage(TextFormat::YN . "You cannot use unicode characters without a rank!");
			return;
		}
		$text = $player->hasRank() ? Chat::convertWithEmojis($text) : $text;
		if(strlen($text) >= 50){
			$player->sendMessage(TextFormat::YN . "Item name must be within 50 characters!");
			return;
		}

		$player->showModal(new ConfirmRenameUi($item, $text));
	}

}