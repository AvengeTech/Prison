<?php namespace prison\trade;

use prison\trade\request\TradeRequest;

use core\session\component\BaseComponent;

class TradeComponent extends BaseComponent{

	public int $ticks = 0;

	public array $incoming = [];
	public array $outgoing = [];

	public ?TradeSession $tradesession = null;

	public function getName() : string{
		return "trade";
	}

	public function tick() : void{
		$this->ticks++;
		if($this->ticks %4 !== 0) return;
		
		if($this->isTrading()){
			$this->getTradeSession()->tick();
		}else{
			foreach($this->getIncomingRequests() as $request) $request->tick();
			foreach($this->getOutgoingRequests() as $request) $request->tick();
		}
	}

	public function isTrading() : bool{
		return $this->tradesession !== null;
	}

	public function setTrading(?TradeSession $session = null) : void{
		$this->tradesession = $session;
	}

	public function getTradeSession() : ?TradeSession{
		return $this->tradesession;
	}

	public function getIncomingRequests() : array{
		return $this->incoming;
	}

	public function getIncomingRequest(int $id) : ?TradeRequest{
		return $this->incoming[$id] ?? null;
	}

	public function addIncoming(TradeRequest $request) : void{
		$this->incoming[$request->getId()] = $request;
	}

	public function removeIncoming(TradeRequest $request) : void{
		$request = $this->incoming[($id = $request->getId())] ?? null;
		if($request !== null){
			$request->close();
			unset($this->incoming[$id]);
		}
	}

	public function removeAllIncoming(bool $silent = true, string $reason = "Unknown") : void{
		foreach($this->getIncomingRequests() as $request){
			$request->decline($silent, $reason);
		}
	}

	public function getOutgoingRequests() : array{
		return $this->outgoing;
	}

	public function getOutgoingRequest(int $id) : ?TradeRequest{
		return $this->outgoing[$id] ?? null;
	}

	public function addOutgoing(TradeRequest $request) : void{
		$this->outgoing[$request->getId()] = $request;
	}

	public function removeOutgoing(TradeRequest $request) : void{
		$request = $this->outgoing[($id = $request->getId())] ?? null;
		if($request !== null){
			$request->close();
			unset($this->outgoing[$id]);
		}
	}

	public function removeAllOutgoing(bool $silent = true, string $reason = "Unknown") : void{
		foreach($this->getOutgoingRequests() as $request){
			$request->decline($silent, $reason); //todo: cancel..?
		}
	}

}