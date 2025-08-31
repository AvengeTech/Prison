<?php namespace prison\blocktournament\uis;

use pocketmine\Server;
use pocketmine\player\Player;

use prison\Prison;
use prison\PrisonPlayer;
use prison\blocktournament\Game;

use core\Core;
use core\ui\elements\customForm\{
	Label,
	Input
};
use core\ui\windows\CustomForm;
use core\user\User;
use core\utils\TextFormat;

class NewInviteUi extends CustomForm{
	
	public function __construct(Player $player, public Game $game, string $error = ""){
		parent::__construct("Invite New Player");
		$this->addElement(new Label("Enter the name(s) of the player(s) you'd like to invite. If multiple, separate with commas."));
		$this->addElement(new Input("Enter username(s)", "E.g. m4l0ne23, KCPrimeMCYT, etc.."));
	}

	public function handle($response, Player $player){
		/** @var PrisonPlayer $player */
		$game = Prison::getInstance()->getBlockTournament()->getGameManager()->getGameFrom($this->game);

		$players = explode(", ", $response[1]);
		Core::getInstance()->getUserPool()->useUsers($players, function(array $users) use($player, $game) : void{
			if(!$player->isConnected()) return;
			$invites = [];
			foreach($users as $user){
				if($user->validXuid() && $user->getXuid() != $player->getXuid()){
					$invites[] = $user;
				}
			}
			$count = 0;
			foreach($invites as $invite){
				if($game->addInvite($user)){
					$count++;
					$p = $user->getPlayer();
					if($p instanceof Player){
						$p->sendMessage(TextFormat::GI . "You have been invited to " . TextFormat::YELLOW . $game->getCreatorName() . "'s " . TextFormat::GRAY . "Block Tournament! Type " . TextFormat::YELLOW . "/bt invites " . TextFormat::GRAY . "for more information!");
					}
				}
			}
			$player->showModal(new ManageInvitesUi($player, $game, TextFormat::GREEN . "Successfully invited " . $count . " new players!"));
		});
	}

}