<?php namespace prison\blocktournament\commands;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;


use pocketmine\player\Player;

use pocketmine\plugin\Plugin;

use prison\Prison;
use prison\PrisonPlayer;
use prison\blocktournament\uis\{
	CreateGameUi,
	GameDetailsUi,
	DropoutConfirmUi,
	GameResultsUi,
	MyInvitesUi,
	ManageGameUi,
	GameModUi,
	CancelGameUi,
	CommandHelpUi
};

use core\utils\TextFormat;

class BtCommand extends Command{

	public $plugin;

	public function __construct(Prison $plugin, $name, $description){
		$this->plugin = $plugin;
		parent::__construct($name,$description);
		$this->setPermission("prison.perm");
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args){
		$sender->sendMessage(TextFormat::RI . "Block tournaments are temporarily disabled!");
		return;
		
		/** @var PrisonPlayer $sender */
		if(!$sender instanceof Player) return false;

		$bt = $this->plugin->getBlockTournament();
		$option = strtolower((array_shift($args) ?? "auto"));
		switch($option){
			case "auto":
				$game = $bt->getGameManager()->getPlayerGame($sender);
				if($game !== null){
					$sender->showModal(new GameDetailsUi($game));
					return true;
				}
				$sender->showModal(new CreateGameUi($sender));
				return true;
			case "join":
			case "j":
				if($sender->isBattleSpectator()){
					$sender->sendMessage(TextFormat::RI . "You cannot join a block tournament while spectating a gang battle!");
					return false;
				}
				if($sender->isBattleParticipant()){
					$sender->sendMessage(TextFormat::RI . "You cannot join a block tournament while in a gang battle!");
					return false;
				}
				if($sender->getGameSession()->getKoth()->inGame()){
					$sender->sendMessage(TextFormat::RI . "You cannot join a block tournament while in a koth event!");
					return false;
				}

				if(count($args) == 0){
					$game = $bt->getGameManager()->getPlayerGame($sender);
					if($game !== null){
						$sender->sendMessage(TextFormat::RI . "You are already in a Block Tournament! Type " . TextFormat::YELLOW . "/bt details " . TextFormat::GRAY . "for more information.");
						return false;
					}

					$game = $bt->getGameManager()->getPublicGame();
					if($game == null){
						$sender->sendMessage(TextFormat::RI . "There is no running Block Tournament right now.");
						return false;
					}

					if(!$game->addPlayer($sender)){
						$sender->sendMessage(TextFormat::RI . "An error occured when attempting to put you in the Block Tournament!");
						return false;
					}
					$sender->sendMessage(TextFormat::GI . "Successfully joined the public Block Tournament!");
					return false;
				}
			case "drop":
			case "quit":
			case "q":
			case "leave":
				$game = $bt->getGameManager()->getPlayerGame($sender);
				if($game !== null){
					$sender->showModal(new DropoutConfirmUi());
					return true;
				}
				$sender->sendMessage(TextFormat::RI . "You are not in a Block Tournament!");
			case "details":
			case "detail":
			case "dt":
				$game = $bt->getGameManager()->getPlayerGame($sender);
				if($game !== null){
					$sender->showModal(new GameDetailsUi($game));
					return true;
				}
				$sender->sendMessage(TextFormat::RI . "You are not in a Block Tournament!");
				return false;
			case "results":
			case "result":
			case "r":
				$game = $sender->getGameSession()->getBlockTournament()->getLastGame();
				if($game == null){
					$sender->sendMessage(TextFormat::RI . "You have not recently participated in a Block Tournament!");
					return false;
				}
				$sender->showModal(new GameResultsUi($sender));
				return true;
			case "invites":
			case "invite":
			case "i":
				if($sender->isBattleSpectator()){
					$sender->sendMessage(TextFormat::RI . "You cannot join a block tournament while spectating a gang battle!");
					return false;
				}
				if($sender->isBattleParticipant()){
					$sender->sendMessage(TextFormat::RI . "You cannot join a block tournament while in a gang battle!");
					return false;
				}

				if(count($args) == 0){
					$sender->showModal(new MyInvitesUi($sender));
					return true;
				}
				$name = array_shift($args);
				$player = $this->plugin->getServer()->getPlayerExact($name);
				if(!$player instanceof Player){
					$sender->sendMessage(TextFormat::RI . "No Block Tournament invite found from " . TextFormat::YELLOW . $name);
					return false;
				}
				
				break;
			case "create":
			case "start":
			case "new":
			case "c":
			case "n":
				if($sender->isBattleSpectator()){
					$sender->sendMessage(TextFormat::RI . "You cannot start a block tournament while spectating a gang battle!");
					return false;
				}
				if($sender->isBattleParticipant()){
					$sender->sendMessage(TextFormat::RI . "You cannot start a block tournament while in a gang battle!");
					return false;
				}
				if($sender->getGameSession()->getKoth()->inGame()){
					$sender->sendMessage(TextFormat::RI . "You cannot start a block tournament while in a koth event!");
					return false;
				}
				$sender->showModal(new CreateGameUi($sender));
				return true;
			case "manage":
			case "m":
			case "edit":
			case "e":
				$game = $bt->getGameManager()->getPlayerGame($sender);
				if($game == null){
					$sender->sendMessage(TextFormat::RI . "You are not in a Block Tournament!");
					return false;
				}
				if(!$game->canEdit($sender)){
					$sender->sendMessage(TextFormat::RI . "You do not have permission to modify this game!");
					return false;
				}
				$sender->showModal(new ManageGameUi($sender));
				break;
			case "mod":
			case "staff":
				if(!$sender->isStaff()){
					$sender->sendMessage(TextFormat::RI . "You cannot use this command!");
					return false;
				}
				if(count($args) == 0){
					$sender->showModal(new GameModUi($sender));
					return true;
				}
				$gm = Prison::getInstance()->getBlockTournament()->getGameManager();
				break;
			case "cancel":
			case "stop":
			case "s":
				$game = $bt->getGameManager()->getPlayerGame($sender);
				if($game == null){
					$sender->sendMessage(TextFormat::RI . "You are not in a Block Tournament!");
					return false;
				}
				if(!$game->canEdit($sender)){
					$sender->sendMessage(TextFormat::RI . "You do not have permission to modify this game!");
					return false;
				}
				$sender->showModal(new CancelGameUi($game));
				break;
			case "help":
			default:
				$sender->showModal(new CommandHelpUi($sender));
		}

	}

	public function getPlugin() : Plugin{
		return $this->plugin;
	}

}