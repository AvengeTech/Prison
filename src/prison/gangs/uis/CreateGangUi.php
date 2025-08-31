<?php namespace prison\gangs\uis;

use pocketmine\player\Player;

use prison\Prison;
use prison\PrisonPlayer;
use prison\gangs\GangManager;

use core\ui\windows\CustomForm;
use core\ui\elements\customForm\{
	Label,
	Input,
	Toggle
};
use core\utils\TextFormat;

class CreateGangUi extends CustomForm{

	const NAME_LIMIT = 16;

	public function __construct(Player $player, string $name = "", string $description = "Join my gang!", string $error = ""){
		$gang = Prison::getInstance()->getGangs()->getGangManager()->getPlayerGang($player);
		if($gang != null) return;

		parent::__construct("Create New Gang");
		$this->addElement(new Label(($error == "" ? "" : TextFormat::RED . "Error: " . $error . TextFormat::WHITE . PHP_EOL . PHP_EOL) . "Fill out the format below to create a gang! (NOTE: Creating a gang costs " . TextFormat::AQUA . number_format(GangManager::GANG_PRICE) . " Techits" . TextFormat::WHITE . ". If you are caught with an inappropriate gang name, your gang will be deleted without warning.)"));

		$this->addElement(new Input("Name:", "My Gang", $name));
		$this->addElement(new Input("Description:", "...", $description));

		$this->addElement(new Toggle("If all of the information above is correct, check the box and press 'Submit' to create your gang!"));
	}

	public function handle($response, Player $player) {
		/** @var PrisonPlayer $player */
		$gang = ($gm = Prison::getInstance()->getGangs()->getGangManager())->getPlayerGang($player);
		if($gang != null){
			$player->sendMessage(TextFormat::RI . "You are already in a gang!");
			return;
		}
		
		$name = TextFormat::clean($response[1]);
		$description = $response[2];

 		if($player->getTechits() < ($price = GangManager::GANG_PRICE) && !$player->isTier3()){
			$player->showModal(new CreateGangUi($player, $name, $description, "You must have at least " . TextFormat::AQUA . number_format($price) . " Techits " . TextFormat::RED . "to create a gang!"));
			return false;
		}

		if(strlen($name) > self::NAME_LIMIT && !$player->isTier3()){
			$player->showModal(new CreateGangUi($player, $name, $description, "Your chosen gang name is too long! Please shorten it and try again!"));
			return;
		}
		if(!ctype_alnum($name) && !$player->isTier3()){
			$player->showModal(new CreateGangUi($player, $name, $description, "Gang name must only consist of alphanumeric characters (A-Z, 0-9)! Please try again!"));
			return;
		}

		$gm->doesGangNameExist($name, function(bool $exists) use($player, $gm, $name, $description, $response) : void{
			if(!$player->isConnected()) return;
			if($exists){
				$player->showModal(new CreateGangUi($player, $name, $description, "A gang by this name already exists! Please enter a different name and try again."));
				return;
			}

			if(!$response[3]){
				$player->showModal(new CreateGangUi($player, $name, $description, "Please check the box at the bottom of the page and try again!"));
				return;
			}

			$gm->createGang($player, $name, $description);
			$player->takeTechits(GangManager::GANG_PRICE);
			$player->sendMessage(TextFormat::GI . "Successfully created a gang! Type " . TextFormat::YELLOW . "/g invite" . TextFormat::GRAY . " to recruit your first member!");

		});
	}

}