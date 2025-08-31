<?php namespace prison\gangs\objects;

use pocketmine\player\Player;

use core\user\User;
use core\utils\TextFormat;

class InviteManager{

	public static int $inviteId = 0;
	
	public array $invites = [];

	public function __construct(public Gang $gang){}

	public function getGang() : Gang{
		return $this->gang;
	}

	public function tick() : void{
		foreach($this->getInvites() as $invite){
			if(!$invite->tick()){
				$this->removeInvite($invite);
				$from = $invite->getFrom();
				$fp = $from->getPlayer();
				$fg = $from->getGamertag();

				$to = $invite->getTo();
				$tp = $to->getPlayer();
				$tg = $to->getGamertag();

				if($fp instanceof Player){
					$fp->sendMessage(TextFormat::YI . "Gang invitation sent to " . TextFormat::YELLOW . $tg . TextFormat::GRAY . " has expired!"); 
				}

				if($tp instanceof Player){
					$tp->sendMessage(TextFormat::YI . "Gang invitation from " . TextFormat::YELLOW . $fg . TextFormat::GRAY . " to " . TextFormat::AQUA . $this->getGang()->getName() . TextFormat::GRAY . " has expired!");
				}
			}
		}
	}

	public function getInvites() : array{
		return $this->invites;
	}

	public function addInvite($invite, bool $send = false) : bool{
		if($this->exists($invite)) return false;
		$this->invites[$invite->getName()] = $invite;
		if(($pl = $invite->getTo()->getPlayer()) instanceof Player)
			$pl->sendMessage(TextFormat::GI . "You have received a new gang invitation! Type " . TextFormat::YELLOW . "/g invites " . TextFormat::GRAY . "to view it!");

		if($send) $invite->sync(GangInvite::SYNC_CREATE);
		return true;
	}

	public function exists(Player|User|GangInvite $player) : bool{
		return isset($this->invites[$player->getName()]);
	}

	public function getInvite(Player|User|GangInvite $player){
		return $this->invites[$player->getName()] ?? null;
	}

	public function removeInvite(Player|User|GangInvite $player, bool $send = false) : bool{
		if(!$this->exists($player)) return false;
		$invite = $this->getInvite($player);
		unset($this->invites[$player->getName()]);
		if($send) $invite->sync(GangInvite::SYNC_DELETE);
		return true;
	}

}