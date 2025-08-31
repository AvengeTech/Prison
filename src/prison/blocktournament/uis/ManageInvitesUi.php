<?php namespace prison\blocktournament\uis;

use pocketmine\player\Player;

use core\ui\windows\SimpleForm;
use core\ui\elements\simpleForm\Button;

use prison\Prison;
use prison\PrisonPlayer;
use prison\blocktournament\Game;

use core\utils\TextFormat;

class ManageInvitesUi extends SimpleForm{

	public $game;
	public $invites = [];

	public function __construct(Player $player, ?Game $game = null, $message = ""){
		parent::__construct("Manage Invites", ($message !== "" ? $message . TextFormat::WHITE . PHP_EOL . PHP_EOL : "") . "Select an option below.");

		$gm = Prison::getInstance()->getBlockTournament()->getGameManager();
		if($game == null) $game = $gm->getPlayerGame($player);
		$game = $this->game = $gm->getGameFrom($game);

		$this->addButton(new Button("Add player"));
		foreach($game->getInvites() as $invite){
			$this->invites[] = $invite;
			$this->addButton(new Button($invite->getGamertag() . PHP_EOL . "Tap to revoke invite"));
		}
		$this->addButton(new Button("Go back"));
	}

	public function handle($response, Player $player){
		/** @var PrisonPlayer $player */
		$game = Prison::getInstance()->getBlockTournament()->getGameManager()->getGameFrom($this->game);
		if(!$game->canEdit($player)){
			$player->sendMessage(TextFormat::RI . "You do not have permission to edit this tournament!");
			return;
		}
		if(!$game->isActive()){
			$player->sendMessage(TextFormat::RI . "This tournament has ended!");
			return;
		}
		if($response == 0){
			$player->showModal(new NewInviteUi($player, $game));
			return;
		}
		foreach($this->invites as $key => $invite){
			if($key + 1 == $response){
				$game->removeInvite($invite);
				$player->showModal(new ManageInvitesUi($player, $game, TextFormat::GREEN . "Successfully revoked " . $invite->getGamertag() . "'s invitation."));
				return;
			}
		}
		$player->showModal(new ManageGameUi($player, $game));
	}

}