<?php namespace prison\gangs\objects;

use pocketmine\player\Player;

use prison\Prison;

use core\Core;
use core\network\protocol\ServerSubUpdatePacket;
use core\user\User;
use core\utils\TextFormat;

class GangInvite{

	const SYNC_CREATE = 0;
	const SYNC_DELETE = 1;

	const MAX_LIFESPAN = 60;

	public int $lifespan = 0;

	public function __construct(
		public Gang $gang,
		public User $to,
		public User $from
	){}

	public function getGang() : Gang{
		return $this->gang;
	}

	public function tick() : bool{
		$this->lifespan++;
		return $this->lifespan < self::MAX_LIFESPAN;
	}

	/**
	 * Used to identify who invite goes to
	 */
	public function getName() : string{
		return $this->getTo()->getGamertag();
	}

	public function getTo() : User{
		return $this->to;
	}

	public function getFrom() : User{
		return $this->from;
	}

	public function accept() : void{
		$from = $this->getFrom()->getPlayer();
		if($from instanceof Player){
			$from->sendMessage(TextFormat::GI . TextFormat::AQUA . $this->getTo()->getGamertag() . TextFormat::GRAY . " has accepted your gang invite!");
		}

		$to = $this->getTo();

		$gang = Prison::getInstance()->getGangs()->getGangManager()->getGangById($this->getGang()->getId());
		$member = new GangMember($gang, $to);
		$gang->getMemberManager()->addMember($member, true);

		$tp = $to->getPlayer();
		if($tp instanceof Player)
			$tp->sendMessage(TextFormat::GI . "Welcome to '" . TextFormat::YELLOW . $gang->getName() . TextFormat::GRAY . "'!");

		$this->terminate();
	}

	public function decline() : void{
		$from = $this->getFrom()->getPlayer();
		if($from instanceof Player){
			$from->sendMessage(TextFormat::RI . TextFormat::AQUA . $this->getTo()->getGamertag() . TextFormat::GRAY . " has declined your gang invite!");
		}

		$this->terminate();
	}

	final public function terminate() : void{
		Prison::getInstance()->getGangs()->getGangManager()->getGangById($this->getGang()->getId())->getInviteManager()->removeInvite($this, true);
	}

	public function verify() : bool{
		return $this->getGang()->getInviteManager()->exists($this);
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
				"gang" => $this->getGang()->getId(),
				"type" => Gang::SYNC_INVITE,
				"change" => $type,
				"player" => $this->getTo()->getGamertag(),
				"from" => $this->getFrom()->getGamertag()
			]
		]))->queue();
	}

}
