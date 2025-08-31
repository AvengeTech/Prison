<?php namespace prison\tags\commands;

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

class AddRandomTags extends Command{
	
	public function __construct(public Prison $plugin, string $name, string $description){
		parent::__construct($name, $description);
		$this->setPermission("prison.tier3");
		$this->setAliases(["art"]);
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
			$sender->sendMessage(TextFormat::RI . "Usage: /addrandomtags <player> <amount>");
			return;
		}
		$name = array_shift($args);
		$amount = (int) array_shift($args);

		Core::getInstance()->getUserPool()->useUser($name, function(User $user) use($sender, $amount) : void{
			if(!$user->valid()){
				$sender->sendMessage(TextFormat::RI . "Player never seen!");
				return;
			}
			Prison::getInstance()->getSessionManager()->useSession($user, function(PrisonSession $session) use($sender, $user, $amount) : void{
				$tags = Prison::getInstance()->getTags();
				$new = [];
				$tdh = $session->getTags()->getTagsNoHave();
				if(count($tdh) <= $amount){
					$new = $tdh;
					$total = count($new);
				}else{
					$total = 0;
					while($total < $amount){
						$tag = $tags->getRandomTag($tdh);
						if(!in_array($tag, $new)){
							$new[] = $tag;
							$total++;
						}
					}
				}
				foreach($new as $t){
					$session->getTags()->addTag($t);
				}
				if($user->validPlayer()){
					$user->getPlayer()->sendMessage(TextFormat::GI . "You just received " . TextFormat::GREEN . $total . TextFormat::GRAY . " new tags!");
				}else{
					$session->getTags()->saveAsync();
				}
				$sender->sendMessage(TextFormat::GI . "Gave " . $user->getGamertag() . " " . $total . " random tags!");
			});
		});
	}

	public function getPlugin() : Plugin{
		return $this->plugin;
	}

}