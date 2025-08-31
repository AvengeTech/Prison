<?php namespace prison\trade;

use pocketmine\Server;
use pocketmine\inventory\Inventory;
use pocketmine\player\Player;

use prison\PrisonPlayer;
use prison\trade\inventory\TradeInventory;

use core\utils\TextFormat;

class TradeSession{
	
	public string $player1;
	public string $player2;

	public TradeInventory $inventory;

	public function __construct(public int $id, Player $player1, Player $player2){
		$this->player1 = $player1->getName();
		$this->player2 = $player2->getName();

		$this->inventory = new TradeInventory($this);
		$this->inventory->setup();
	}

	public function tick() : void{
		$this->getInventory()->tick();
	}

	public function getId() : int{
		return $this->id;
	}

	public function getPlayer1() : ?PrisonPlayer{
		return Server::getInstance()->getPlayerExact($this->player1);
	}

	public function getPlayer1Session(){
		return $this->getPlayer1()->getGameSession()->getTrade();
	}

	public function getPlayer2() : ?PrisonPlayer{
		return Server::getInstance()->getPlayerExact($this->player2);
	}

	public function getPlayer2Session(){
		return $this->getPlayer2()->getGameSession()->getTrade();
	}

	public function getInventory() : TradeInventory{
		return $this->inventory;
	}

	public function open(Player $player) : void{
		if($player !== $this->getPlayer1() && $player !== $this->getPlayer2()){
			return;
		}
		$player->getNetworkSession()->getInvManager()->getContainerOpenCallbacks()->add(function(int $id, Inventory $inventory) : array{
			return []; //trollface
		});
		$player->setCurrentWindow($this->getInventory());
	}

	public function complete() : void{
		$inventory = $this->getInventory();

		($s1 = $this->getPlayer1Session())->setTrading();
		if(($pl = $s1->getPlayer()) instanceof Player) $pl->sendMessage(TextFormat::GI . "Trade has been completed!");

		($s2 = $this->getPlayer2Session())->setTrading();
		if(($pl = $s2->getPlayer()) instanceof Player) $pl->sendMessage(TextFormat::GI . "Trade has been completed!");
	}

}