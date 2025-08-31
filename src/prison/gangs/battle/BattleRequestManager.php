<?php namespace prison\gangs\battle;

use prison\Prison;
use prison\gangs\objects\Gang;

class BattleRequestManager{

	public $gang;

	public $requests = [];

	public function __construct(Gang $gang){
		$this->gang = $gang;
	}

	public function tick() : void{
		foreach($this->getRequests() as $request){
			if(!$request->tick()){
				$request->expire();
				$this->removeRequest($request);
			}
		}
	}

	public function getGang() : Gang{
		return $this->gang;
	}

	public function getSentRequests() : array{
		$requests = [];
		foreach(Prison::getInstance()->getGangs()->getGangManager()->getGangs() as $gang){
			if(($brm = $gang->getBattleRequestManager())->hasOpenRequest($this->getGang())){
				$requests[] = $brm->getRequestFrom($this->getGang());
			}
		}
		return $requests;
	}

	public function getRequests() : array{
		return $this->requests;
	}

	public function addRequest(BattleRequest $request, bool $sync = true) : bool{
		if($this->hasOpenRequest($request->getRequesting())) return false;
		$this->requests[$request->getRequesting()->getId()] = $request;
		if($sync) $request->sync(BattleRequest::SYNC_CREATE);
		return true;
	}

	public function removeRequest(BattleRequest $request) : bool{
		if(!$this->hasOpenRequest($request->getRequesting())) return false;
		unset($this->requests[$request->getRequesting()->getId()]);
		return true;
	}

	public function hasOpenRequest(Gang $gang) : bool{
		return isset($this->requests[$gang->getId()]);
	}

	public function getRequestFrom(Gang $gang) : ?BattleRequest{
		return $this->requests[$gang->getId()] ?? null;
	}

}