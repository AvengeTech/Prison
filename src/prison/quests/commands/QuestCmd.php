<?php namespace prison\quests\commands;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;

use pocketmine\player\Player;
use pocketmine\plugin\Plugin;

use prison\Prison;
use prison\PrisonPlayer;
use prison\quests\uis\{
	QuestStatusUi,
	MainQuestUi,
	QuestAcceptUi
};

use core\utils\TextFormat;
use pocketmine\Server;

class QuestCmd extends Command{
	
	public function __construct(public Prison $plugin, string $name, string $description){
		parent::__construct($name, $description);
		$this->setPermission("prison.perm");
		$this->setAliases(["q"]);
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args) : void {
		/** @var PrisonPlayer $sender */
		if($sender instanceof Player){
			if(count($args) == 0){
				$sender->showModal(new MainQuestUi($sender));
				return;
			}

			$quests = Prison::getInstance()->getQuests();
			$session = $sender->getGameSession()->getQuests();

			$action = strtolower(array_shift($args));
			switch($action){
				case "reqrand":
					if($session->hasActiveQuest()){
						$sender->sendMessage(TextFormat::RN . "You already have a quest at hand! Finish this quest first.");
						return;
					}
					$sender->showModal(new QuestAcceptUi($sender));
					break;
				case "status":
					if(!$session->hasActiveQuest()){
						$sender->sendMessage(TextFormat::RN . "You currently have no active quest.");
						return;
					}
					$sender->showModal(new QuestStatusUi($sender));
					break;
				case "points":
					if(!$sender->isTier3()) return;

					if(count($args) === 0){
						$sender->sendMessage("/quest points <player> [amount]");
						return;
					}

					/** @var ?PrisonPlayer $player */
					$player = Server::getInstance()->getPlayerByPrefix(array_shift($args));

					if(is_null($player)){
						$sender->sendMessage(TextFormat::RI . "Player is not online");
						return;
					}

					$points = (count($args) === 0 ? 1 : array_shift($args));

					$player->getGameSession()->getQuests()->addPoints($points);

					$player->sendMessage(TextFormat::GN . "You were given " . TextFormat::MINECOIN_GOLD . $points . " quest points" . TextFormat::GRAY . ".");
					$sender->sendMessage(TextFormat::GI . "You gave " . TextFormat::AQUA . $player->getName() . TextFormat::GRAY . ", " . TextFormat::MINECOIN_GOLD . $points . " quest points" . TextFormat::GRAY . ".");
					break;
			}
		}
	}

	public function getPlugin() : Plugin{
		return $this->plugin;
	}

}