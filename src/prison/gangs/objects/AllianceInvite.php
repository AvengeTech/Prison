<?php namespace prison\gangs\objects;

use pocketmine\player\Player;

use prison\Prison;

use core\Core;
use core\network\protocol\ServerSubUpdatePacket;
use core\user\User;
use core\utils\TextFormat;

class AllianceInvite{

	const SYNC_CREATE = 0;
	const SYNC_DELETE = 1;

	const MAX_LIFESPAN = 60;

	public int $lifespan = 0;

	public function __construct(
		public int $gangId,
		public int $allyId,
		public User $user,
		public string $message = ""
	){}

	public function tick() : bool{
		$this->lifespan++;
		return $this->lifespan < self::MAX_LIFESPAN;
	}

	public function getAllianceManager() : AllianceManager{
		return Prison::getInstance()->getGangs()->getGangManager()->getAllianceManager();
	}

	public function getGang() : ?Gang{
		return Prison::getInstance()->getGangs()->getGangManager()->getGangById($this->getGangId());
	}

	public function getGangId() : int{
		return $this->gangId;
	}

	public function getAlly() : ?Gang{
		return Prison::getInstance()->getGangs()->getGangManager()->getGangById($this->getAllyId());
	}

	public function getAllyId() : int{
		return $this->allyId;
	}

	public function getUser() : User{
		return $this->user;
	}

	public function getMessage() : string{
		return $this->message;
	}

	public function accept() : bool{
		$player = $this->getUser()->getPlayer();
		if($player instanceof Player){
			$player->sendMessage(TextFormat::GI . "Your alliance request to " . TextFormat::YELLOW . $this->getGang()->getName() . TextFormat::GRAY . " has been accepted!");
		}

		$this->terminate();
		return $this->getAllianceManager()->addAlliance($this->getGangId(), $this->getAllyId());
	}

	public function decline() : bool{
		$player = $this->getUser()->getPlayer();
		if($player instanceof Player){
			$player->sendMessage(TextFormat::RI . "Your alliance request to " . TextFormat::YELLOW . $this->getGang()->getName() . TextFormat::GRAY . " has been declined!");
		}

		return $this->terminate();
	}

	public function cancel() : bool{
		return $this->terminate();
	}

	final public function terminate() : bool{
		$gang = $this->getGang();
		if($gang instanceof Gang){
			$gang->getAllianceInviteManager()->removeInvite($this, true);
			return true;
		}
		return false;
	}

	public function verify() : bool{
		return $this->getGang()->getAllianceInviteManager()->exists($this);
	}

	public function sync(int $type) : void{
		$servers = [];
		foreach(Core::thisServer()->getSubServers(false, true) as $server){
			$servers[] = $server->getIdentifier();
		}
		(new ServerSubUpdatePacket([
			"server" => $servers,
			"type" => "gangSync",
			"data" => [
				"gang" => $this->getGangId(),
				"type" => Gang::SYNC_ALLIANCE_INVITE,
				"player" => $this->getUser()->getGamertag(),
				"change" => $type,
				"ally" => $this->getAllyId(),
				"message" => $this->getMessage(),
			]
		]))->queue();
	}

}