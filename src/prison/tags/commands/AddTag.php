<?php namespace prison\tags\commands;

use pocketmine\Server;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\plugin\Plugin;

use prison\Prison;
use prison\PrisonPlayer;
use prison\PrisonSession;

use core\Core;
use core\user\User;
use core\utils\TextFormat;

class AddTag extends Command{
	
	public function __construct(public Prison $plugin, string $name, string $description){
		parent::__construct($name, $description);
		$this->setPermission("prison.tier3");
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args){
		/** @var PrisonPlayer $sender */
		if($sender instanceof Player){
			if(!$sender->isTier3()){
				$sender->sendMessage(TextFormat::RI . "This command is for the owner only!");
				return;
			}
		}
		if(count($args) != 2){
			$sender->sendMessage(TextFormat::RI . "Usage: /addtag <player> <tagname>");
			return;
		}

		$name = array_shift($args);

		$tag = array_shift($args);
		$tags = Prison::getInstance()->getTags();
		$tag = $tags->getTag($tag);

		if($tag === null){
			$sender->sendMessage(TextFormat::RI . "Tag doesn't exist!");
			return;
		}

		Core::getInstance()->getUserPool()->useUser($name, function(User $user) use($sender, $tag) : void{
			if(!$user->valid()){
				$sender->sendMessage(TextFormat::RI . "Player never seen!");
				return;
			}
			Prison::getInstance()->getSessionManager()->useSession($user, function(PrisonSession $session) use($sender, $user, $tag) : void{
				if($session->getTags()->hasTag($tag)){
					$sender->sendMessage(TextFormat::RI . $user->getGamertag() . " already has this tag!");
					return;
				}
				$session->getTags()->addTag($tag);
				if(!$user->validPlayer()){
					$session->getTags()->saveAsync();
				}
				$sender->sendMessage(TextFormat::GI . "Gave " . $user->getGamertag() . " the " . $tag->getName() . " tag");
			});
		});
	}

	public function getPlugin() : Plugin{
		return $this->plugin;
	}

}