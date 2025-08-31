<?php namespace prison\combat\ui\bounty;

use pocketmine\Server;
use pocketmine\player\Player;

use prison\Prison;
use prison\PrisonPlayer;

use core\ui\windows\CustomForm;
use core\ui\elements\customForm\{
	Label,
	Input
};
use core\utils\TextFormat;

class ViewBountyUi extends CustomForm{

	public bool $aval = false;
	public int $value;

	public function __construct(public Player $player, public string $name, string $error = "") {
		/** @var PrisonPlayer $player */
		parent::__construct("View Bounty");
		$this->name = $name;

		$bl = Prison::getInstance()->getCombat()->getBounties();
		$value = 0;
		foreach($bl as $n => $value){
			if($n == $name){
				$this->aval = true;
				$this->value = $value;
				break;
			}
		}
		if(!$this->aval){
			$error = "Player not online. Please close this menu";
		}elseif($value <= 0){
			$error = "Player doesn't have an existing bounty. Please close this menu and goto the Create Bounty menu";
		}
		$this->addElement(new Label(($error != "" ? TextFormat::RED . TextFormat::BOLD . "Error: " . TextFormat::RESET . TextFormat::RED . $error . TextFormat::WHITE . "\n" : "") . $name . "'s bounty is worth " . TextFormat::AQUA . $value . " Techits"));

		$this->addElement(new Label("You have " . TextFormat::AQUA . $player->getTechits() . " Techits"));

		$this->addElement(new Input("Add Bounty Value", "Minimum value: 1000", "1000"));

		$this->addElement(new Label(((!$this->aval) ? "Press 'Submit' to go back" : "To set a bounty on the specified player's head, press Submit!")));
	}

	public function handle($response, Player $player) {
		/** @var PrisonPlayer $player */
		if(!$this->aval){
			$player->showModal(new BountyUi($player));
			return;
		}
		$p = Server::getInstance()->getPlayerExact($this->name);
		if(!$p instanceof Player){
			$player->showModal(new BountyUi($player, "Player selected is no longer online!"));
			return;
		}
		/** @var PrisonPlayer $p */
		$s = $p->getGameSession()->getCombat();
		if(!$s->hasBounty()){
			$player->showModal(new CreateBountyUi($player, "Player doesn't have existing bounty! Add a new bounty to this player here!"));
			return;
		}
		$value = (int) $response[2];
		if($value < 1000){
			$player->showModal(new ViewBountyUi($player, $this->name, "Bounty value must be at least 1,000"));
			return;
		}
		if($value > $player->getTechits()){
			$player->showModal(new ViewBountyUi($player, $this->name, "You do not have enough techits to do this!"));
			return;
		}
		$s->addBountyValue($value);
		$player->takeTechits($value);
		$p->sendTip(TextFormat::RED . "+" . $value . " to bounty!");
		//$p->sendMessage(TextFormat::RI . TextFormat::YELLOW . $player->getName() . TextFormat::GRAY . " set a bounty of " . TextFormat::AQUA . $value . " Techits" . TextFormat::GRAY . " on your head!");
		$player->sendMessage(TextFormat::GI . "Bounty on " . TextFormat::YELLOW . $p->getName() . TextFormat::GRAY . " extended by " . TextFormat::AQUA . $value . " Techits!");
	}

}