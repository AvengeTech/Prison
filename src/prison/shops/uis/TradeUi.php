<?php namespace prison\shops\uis;

use pocketmine\player\Player;

use prison\shops\pieces\ShopItem;

use core\ui\windows\CustomForm;
use core\ui\elements\customForm\{
	Dropdown,
	Label,
	Input
};
use pocketmine\item\StringToItemParser;
use prison\PrisonPlayer;

class TradeUi extends CustomForm{

	public $item;
	public $categoryId;

	public function __construct(Player $player, ShopItem $item, int $categoryId){
		/** @var PrisonPlayer $player */
		parent::__construct($item->getName());
		$this->item = $item;
		$this->categoryId = $categoryId;

		$session = $player->getGameSession()->getShops();
		$boost = $session->getBoost();
		$active = $session->isActive();

		$this->addElement(new Label("Do you wanna buy this, or are you selling it to me?"));

		$this->addElement(new DropDown("Transaction Type", ["Buy - " . ($item->canBuy() ? ($item->getBuyPrice() * $boost) . " Techits/Item" . ($active ? " (x" . $boost . " boost)" : "") : "Cannot be bought"), "Sell - " . ($item->canSell() ? ($item->getSellPrice() * $session->getBoost()) . " Techits/Item" . ($active ? " (x" . $boost . " boost)" : "") : "Cannot be sold")]));

		$this->addElement(new Label("How many ".$item->getName()."s are we talking about...?"));
		$this->addElement(new Input("Amount", 1, 1));

		$this->addElement(new Label("Press submit to goto the transaction confirmation page."));
	}

	public function handle($response, Player $player){
		/** @var PrisonPlayer $player */
		$type = $response[1];
		$amount = (int) $response[3];
		if($amount < 1){
			$player->showModal(new ErrorUi($this->categoryId, "Amount must be a positive number!"));
			return;
		}

		if($type == 0){
			if(!$this->item->canBuy()){
				$player->showModal(new ErrorUi($this->categoryId, "This item cannot be purchased"));
				return;
			}
			$total = $this->item->getBuyPrice() * $amount;
			if($player->getTechits() < $total){
				$player->showModal(new ErrorUi($this->categoryId, "You don't have enough Techits to make this purchase"));
				return;
			}
			$player->showModal(new ConfirmUi($player, $type, $this->item, $amount, $this->categoryId));
			return;
		}
		if($type == 1){
			if(!$this->item->canSell()){
				$player->showModal(new ErrorUi($this->categoryId, "This item cannot be sold"));
				return;
			}
			$item = StringToItemParser::getInstance()->parse($this->item->getName())?->setCount($amount);
			if(!$player->getInventory()->contains($item)){
				$player->showModal(new ErrorUi($this->categoryId, "You don't have enough items to sell"));
				return;
			}
			$player->showModal(new ConfirmUi($player, $type, $this->item, $amount, $this->categoryId));
			return;
		}
		$player->showModal(new ErrorUi($this->categoryId, "Wtf"));
	}

}