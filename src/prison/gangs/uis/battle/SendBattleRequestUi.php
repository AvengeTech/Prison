<?php namespace prison\gangs\uis\battle;

use pocketmine\player\Player;

use prison\Prison;
use prison\PrisonPlayer;
use prison\gangs\objects\Gang;
use prison\gangs\battle\BattleRequest;

use core\ui\windows\CustomForm;
use core\ui\elements\customForm\{
	Label,
	Dropdown
};
use core\utils\TextFormat;

class SendBattleRequestUi extends CustomForm{

	public $gangs = [];
	public $kits = [];
	public $max = [];

	public function __construct(Player $player, Gang $gang){
		$gm = Prison::getInstance()->getGangs()->getGangManager();
		$bm = $gm->getBattleManager();
		$names = [];
		foreach($gm->getGangs() as $g){
			if(
				$gang->getLeaderMember()->isOnline(true) &&
				!$bm->inBattle($g) &&
				$g->getId() !== $gang->getId()
			){
				$this->gangs[] = $g;
				$names[] = $g->getName();
			}
		}
		$kitnames = [];
		foreach($bm->getKits() as $kit){
			$this->kits[] = $kit;
			$kitnames[] = $kit->getName();
		}
		parent::__construct("Send Battle Request");
		$this->addElement(new Label("Please fill out the form below to send a battle request!"));
		$this->addElement(new Dropdown("Online gangs:", $names));
		$this->addElement(new Label("(NOTE: You can only send a battle request to gangs with a leader online!"));
		$this->addElement(new Label("Gameplay:"));
		$this->addElement(new Dropdown("Battle Kit", $kitnames));
		$this->addElement(new Dropdown("Mode", ["No Respawns", "Limited Respawns (3)", "Respawns"]));
		$max = $this->max = range(3, $gang->getMaxMembers());
		foreach($max as $key => $m){
			$max[$key] = (string) $m;
		}
		$this->addElement(new Dropdown("Maximum Participants", $max));

		$this->addElement(new Label("Once you have made sure all data above is correct, press 'Submit' to send this battle request!"));
	}

	public function handle($response, Player $player) {
		/** @var PrisonPlayer $player */
		if(count($this->gangs) == 0){
			$player->sendMessage(TextFormat::RI . "No other gangs are online to battle!");
			return;
		}
		$gm = Prison::getInstance()->getGangs()->getGangManager();
		if(!$gm->inGang($player)){
			$player->sendMessage(TextFormat::RI . "You are not in a gang!");
			return;
		}
		$gang = $gm->getPlayerGang($player);
		if(!$gang->isLeader($player)){
			$player->sendMessage(TextFormat::RI . "You must be gang leader to manage battle requests!");
			return;
		}

		$ogang = $gm->getGangByGang($this->gangs[$response[1]]);
		if(!$ogang instanceof Gang){
			$player->sendMessage(TextFormat::RI . "This gang is no longer online!");
			return;
		}
		if($ogang->inBattle()){
			$player->sendMessage(TextFormat::RI . "This gang is already in a battle! Please try again later");
			return;
		}
		if(!$ogang->getLeaderMember()->isOnline(true)){
			$player->sendMessage(TextFormat::RI . "This gang's leader is no longer online!");
			return;
		}

		$kit = $this->kits[$response[4]];
		$mode = $response[5];
		$max = $this->max[$response[6]];

		$request = new BattleRequest($ogang, $gang, $kit, $mode, $max);
		$bm = $gm->getBattleManager();
		if($bm->hasBattledRecently($gang, $ogang)){
			$player->showModal(new ConfirmNoTrophyBattleUi($player, $gang, $request));
			return;
		}
		if(!$ogang->getBattleRequestManager()->addRequest($request)){
			$player->sendMessage(TextFormat::RI . "This gang already has a battle request from you!");
			return;
		}
		$player->sendMessage(TextFormat::GI . "Successfully sent a battle request to " . TextFormat::YELLOW . $ogang->getName() . "!");
		$ogang->getLeader()->getPlayer()?->sendMessage(TextFormat::YI . "Your gang has received a battle request from " . TextFormat::RED . $gang->getName() . "! " . TextFormat::GRAY . "Type " . TextFormat::YELLOW . "/gang battle " . TextFormat::GRAY . "to view it!");
	}

}
