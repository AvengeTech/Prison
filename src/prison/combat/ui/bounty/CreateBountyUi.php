<?php namespace prison\combat\ui\bounty;

use pocketmine\Server;
use pocketmine\player\Player;

use prison\PrisonPlayer;

use core\ui\windows\CustomForm;
use core\ui\elements\customForm\{
	Dropdown,
	Label,
	Input
};
use core\utils\TextFormat;

class CreateBountyUi extends CustomForm{

	public array $players = [];

	public function __construct(Player $player, string $error = ""){
		parent::__construct("Create Bounty");

		$this->addElement(new Label(($error != "" ? TextFormat::RED . TextFormat::BOLD . "Error: " . TextFormat::RESET . TextFormat::RED . $error . TextFormat::WHITE . "\n" : "") . "Select an online player."));

		$players = ["Go back."];
		/** @var PrisonPlayer $player */
		foreach(Server::getInstance()->getOnlinePlayers() as $p) {
			/** @var PrisonPlayer $p */
			if($p !== $player){
				if($p->hasGameSession() && !$p->getGameSession()->getCombat()->hasBounty()){
					$players[] = $p->getName();
				}
			}
		}
		$this->players = $players;
		$this->addElement(new Dropdown("Players", array_values($players)));
		$this->addElement(new Label("Starting bounty value (Minimum: 1,000 Techits)"));
		$this->addElement(new Label("You have " . TextFormat::AQUA . number_format($player->getTechits()) . " Techits"));
		$this->addElement(new Input("Bounty Value", "Minimum value: 1000", "1000"));
		$this->addElement(new Label("To set a bounty on the specified player's head, press Submit!"));
	}

	public function handle($response, Player $player) {
		/** @var PrisonPlayer $player */
		if($response[1] == 0){
			$player->showModal(new BountyUi($player));
			return;
		}
		$p = Server::getInstance()->getPlayerExact($this->players[$response[1]]);
		if(!$p instanceof Player){
			$player->showModal(new CreateBountyUi($player, "Player selected is no longer online!"));
			return;
		}
		/** @var PrisonPlayer $p */
		$s = $p->getGameSession()->getCombat();
		if($s->hasBounty()){
			$player->showModal(new CreateBountyUi($player, "Player already has bounty! Find in the active bounties menu to extend bounty!"));
			return;
		}
		$value = (int) $response[4];
		if($value < 1000){
			$player->showModal(new CreateBountyUi($player, "Bounty value must be at least 1,000"));
			return;
		}
		if($value > $player->getTechits()){
			$player->showModal(new CreateBountyUi($player, "You do not have enough techits to create a bounty of this value!"));
			return;
		}
		$s->setBountyValue($value);
		$player->takeTechits($value);
		$p->sendMessage(TextFormat::RI . TextFormat::YELLOW . $player->getName() . TextFormat::GRAY . " set a bounty of " . TextFormat::AQUA . number_format($value) . " Techits" . TextFormat::GRAY . " on your head!");
		$player->sendMessage(TextFormat::GI . "Bounty set on " . TextFormat::YELLOW . $p->getName() . TextFormat::GRAY . " worth " . TextFormat::AQUA . number_format($value) . " Techits!");
	}

}