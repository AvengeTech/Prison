<?php namespace prison\trade\request;

use pocketmine\{
	player\Player,
	Server
};

use prison\trade\{
	Trade,
	TradeSession
};

use core\utils\TextFormat;
use prison\PrisonPlayer;

class TradeRequest{

	const REQUEST_LIFE = 60;

	public $id;

	public $from;
	public $to;

	public $ticks = 0;
	public $closed = false;

	public function __construct(Player $from, Player $to){
		$this->id = Trade::$requestCount++;

		$this->from = $from->getName();
		$this->to = $to->getName();

		$to->sendMessage(TextFormat::GI . "You've received a trade request from " . TextFormat::YELLOW . $from->getName() . TextFormat::GRAY . ", type /trade to view trade options.");

		/** @var PrisonPlayer $from */
		/** @var PrisonPlayer $to */
		$fromsession = $from->getGameSession()->getTrade();
		$tosession = $to->getGameSession()->getTrade();

		$fromsession->addOutgoing($this);
		$tosession->addIncoming($this);
	}

	public function tick() : void{
		$this->ticks++;

		if($this->ticks >= (self::REQUEST_LIFE * 2)){ //*2 cuz request is saved to both players and ticked twice.
			if(!$this->closed) $this->decline(false);
		}
	}

	public function getId() : int{
		return $this->id;
	}

	public function getFrom() : ?PrisonPlayer{
		return Server::getInstance()->getPlayerExact($this->from);
	}

	public function getTo() : ?PrisonPlayer{
		return Server::getInstance()->getPlayerExact($this->to);
	}

	public function accept() : void{
		$from = $this->getFrom();
		$to = $this->getTo();

		$from->sendMessage(TextFormat::GI . "Trade request to " . TextFormat::YELLOW . $to->getName() . TextFormat::GRAY . " was accepted. Type " . TextFormat::YELLOW . "/trade open");
		$to->sendMessage(TextFormat::GI . "Trade request from " . TextFormat::YELLOW . $from->getName() . TextFormat::GRAY . " was accepted. Type " . TextFormat::YELLOW . "/trade open");

		$tradesession = new TradeSession($this->getId(), $from, $to);

		$fromsession = $from->getGameSession()->getTrade();
		$tosession = $to->getGameSession()->getTrade();

		$fromsession->setTrading($tradesession);
		$tosession->setTrading($tradesession);

		$fromsession->removeOutgoing($this);
		$tosession->removeIncoming($this);

		$fromsession->removeAllIncoming(false, "Player started another trade");
		$fromsession->removeAllOutgoing();

		$tosession->removeAllIncoming(false, "Player started another trade");
		$tosession->removeAllOutgoing();

		$this->close();
	}

	public function decline($silent = true, $reason = "Unknown") : void{
		$from = $this->getFrom();
		if($from instanceof Player){
			$fromsession = $from->getGameSession()->getTrade();
			$fromsession->removeOutgoing($this);
			if(!$silent){
				$from->sendMessage(TextFormat::GI . "Request has been declined to " . TextFormat::YELLOW . $this->to . TextFormat::GRAY . " Reason: " . $reason);
			}
		}

		$to = $this->getTo();
		if($to instanceof Player){
			$tosession = $to->getGameSession()->getTrade();
			$tosession->removeIncoming($this);
		}

		$this->close();
	}

	public function cancel() : void{
		$this->decline(); //todo: idk?

		$this->close();
	}

	public function close() : void{
		$this->closed = true;
	}

}