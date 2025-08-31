<?php namespace prison\shops\uis;

use pocketmine\item\ItemFactory;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use pocketmine\item\Item;

use prison\Prison;
use prison\shops\pieces\ShopItem;

use core\ui\windows\SimpleForm;
use core\ui\elements\simpleForm\Button;
use pocketmine\item\StringToItemParser;
use prison\PrisonPlayer;

class ConfirmUi extends SimpleForm{

	public $type;
	public $item;
	public $amount;

	public $price;

	public $categoryId;

	public function __construct(Player $player, $type, ShopItem $item, $amount, $categoryId){
		/** @var PrisonPlayer $player */
		$session = $player->getGameSession()->getShops();
		$boost = $session->getBoost();
		$active = $session->isActive();

		if($type == 0){
			$price = ($item->getBuyPrice() * $amount) * $boost;
			$msg = "So, you want to buy " . $amount . " " . $item->getName() . "s from me? That'll cost you about... " . $price . " techits. Are you sure you wanna make this trade?";
		}elseif($type == 1){
			$price = ($item->getSellPrice() * $amount) * $boost;
			$msg = "For " . $amount . " of those " . $item->getName() . "s, I'll give you about.. " . $price . " techits. Sound good?";
		}
		parent::__construct("Confirm Trade", $msg);
		$this->price = $price;

		$this->type = $type;
		$this->item = $item;
		$this->amount = $amount;

		$this->categoryId = $categoryId;

		$this->addButton(new Button("Confirm Trade"));
		$this->addButton(new Button("Back"));
	}

	public function handle($response, Player $player){
		/** @var PrisonPlayer $player */
		if($response == 0){
			if($this->type == 0){
				if($player->getTechits() <= $this->price){
					$player->showModal(new ErrorUi($this->categoryId, "You do not have enough Techits to make this purchase!"));
					return;
				}
				$player->sendMessage(TextFormat::GREEN . "Successfully bought " . $this->amount . " of " . $this->item->getName() . " from the Black Market!");
				$player->getInventory()->addItem($this->item->getItem()->setCount($this->amount));
				$player->takeTechits($this->price);
				return;
			}
			if(!$player->getInventory()->contains(($item = $this->item->getItem()->setCount($this->amount)))){
				$player->showModal(new ErrorUi($this->categoryId, "Your inventory no longer contains items you are trying to sell!"));
				return;
			}
			$player->sendMessage(TextFormat::GREEN . "Successfully sold " . $this->amount . " of " . $this->item->getName() . " to the Black Market!");
			$player->getInventory()->removeItem($item);
			$player->addTechits($this->price);
			return;
		}
		$player->showModal(new CategoryUi(Prison::getInstance()->getShops()->getCategoryById($this->categoryId)));
	}
}