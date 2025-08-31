<?php namespace prison\gangs\uis;

use pocketmine\player\Player;

use prison\Prison;
use prison\PrisonPlayer;
use prison\gangs\objects\GangMember;

use core\ui\windows\CustomForm;
use core\ui\elements\customForm\{
	Label,
	Dropdown,
	Input
};
use core\utils\TextFormat;

class BankManagerUi extends CustomForm{

	public function __construct(Player $player, string $error = "") {
		/** @var PrisonPlayer $player */
		$gang = Prison::getInstance()->getGangs()->getGangManager()->getPlayerGang($player);
		if($gang == null) return;

		parent::__construct("Manage Gang Bank");
		$this->addElement(new Label(($error == "" ? "" : TextFormat::RED . "Error: " . $error . TextFormat::WHITE . PHP_EOL . PHP_EOL) . "Your techits: " . TextFormat::AQUA . number_format($player->getTechits()) . " techits" . TextFormat::WHITE . PHP_EOL . "Your gang's bank balance: " . TextFormat::AQUA . number_format($gang->getBankValue()) . " techits" . TextFormat::WHITE . PHP_EOL . PHP_EOL . "Select the type of bank transaction you would like to make below."));
		$this->addElement(new Dropdown("Transaction:", ["Deposit", "Withdraw" . ($gang->getRole($player) !== 5 ? PHP_EOL . "(Must be LEADER role)" : "")]));

		$this->addElement(new Label("Enter the amount of techits you would like to use below."));
		$this->addElement(new Input("Techits:", "100"));

		$this->addElement(new Label("When you are finished, press 'Submit' below to confirm your transaction!"));
	}

	public function handle($response, Player $player) {
		/** @var PrisonPlayer $player */
		$gang = Prison::getInstance()->getGangs()->getGangManager()->getPlayerGang($player);
		if($gang == null){
			$player->sendMessage(TextFormat::RI . "You are no longer in a gang!");
			return;
		}

		$type = $response[1];
		$amount = (int) $response[3];

		switch($type){
			case 0:
				if($amount <= 0){
					$player->showModal(new BankManagerUi($player, "Amount must be more than 0!"));
					return;
				}
				if($amount > $player->getTechits()){
					$player->showModal(new BankManagerUi($player, "You do not have this many techits to deposit!"));
					return false;
				}
				$gang->addToBank($amount, $player);
				$player->sendMessage(TextFormat::GI . "Successfully deposited " . TextFormat::AQUA . number_format($amount) . " techits " . TextFormat::GRAY . "into your gang's bank!");
				break;
			case 1:
				if($gang->getRole($player) < GangMember::ROLE_CO_LEADER){
					$player->showModal(new BankManagerUi($player, "You must be at least a gang co-leader to withdraw gang techits!"));
					return false;
				}
				if($amount <= 0){
					$player->showModal(new BankManagerUi($player, "Amount must be more than 0!"));
					return false;
				}
				if($amount > $gang->getBankValue()){
					$player->showModal(new BankManagerUi($player, "Your bank does not hold this many techits!"));
					return false;
				}
				$gang->takeFromBank($amount, $player);
				$player->sendMessage(TextFormat::GI . "Successfully withdrew " . TextFormat::AQUA . number_format($amount) . " techits " . TextFormat::GRAY . "from your gang's bank!");
				break;
		}
	}
}