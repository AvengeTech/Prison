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
use core\utils\TextFormat;

class InvitePlayersUi extends CustomForm{

	public function __construct(Player $player, public Game $game, string $error = "", array $invites = []){
		parent::__construct("Invite Players");

		$bt = Prison::getInstance()->getBlockTournament();

		$players = [];
		foreach(Server::getInstance()->getOnlinePlayers() as $pl){
			if($bt->getGameManager()->getPlayerGame($pl) == null && $pl->getXuid() != $player->getXuid()){
				$players[] = $pl->getName();
			}
		}
		$this->addElement(new Label(($error !== "" ? TextFormat::RED . $error . TextFormat::WHITE . PHP_EOL . PHP_EOL : "") . "Online invitable players:" . PHP_EOL . implode(", ", $players)));

		$this->addElement(new Label("Invite players to your Block Tournament by adding them to the list below, separated by commas."));
		$this->addElement(new Label("(You may also add players who are not currently online, but will be soon)"));
		$this->addElement(new Input("Players (Minimum: 1)", "E.g. sn3akrr, KCPrimeMCYT, player3"));
	}

	public function handle($response, Player $player){
		/** @var PrisonPlayer $player */
		$game = $this->game;

		$players = explode(", ", $response[3]);
		Core::getInstance()->getUserPool()->useUsers($players, function(array $users) use($player, $game) : void{
			if(!$player->isConnected()) return;
			$invites = [];
			foreach($users as $user){
				if($user->validXuid() && $user->getXuid() != $player->getXuid()){
					$invites[] = $user;
				}
			}
			if(count($invites) < 1){
				$player->showModal(new InvitePlayersUi($player, $game, "Not enough valid players listed! Please try again!", $invites));
				return;
			}
			$game->setInvites($invites);
			$player->showModal(new ConfirmStartUi($player, $game));
		});
	}

}