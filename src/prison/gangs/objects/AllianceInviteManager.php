<?php namespace prison\gangs\objects;

use prison\Prison;
use prison\gangs\objects\Gang;

class AllianceInviteManager extends InviteManager{

	public function tick() : void{
		foreach($this->getInvites() as $invite){
			if(!$invite->tick()){
				$invite->decline();
			}
		}
	}

	public function getOutgoingInvites() : array{
		$og = [];
		$gm = Prison::getInstance()->getGangs()->getGangManager();
		$g = $this->getGang();

		foreach($gm->getGangs() as $gang){
			if($gang != $g){
				$aim = $gang->getAllianceInviteManager();
				foreach($aim->getInvites() as $inv){
					if($inv->getAllyId() == $g->getId()){
						$og[] = $inv;
					}
				}
			}
		}

		return $og;
	}

	public function addInvite($invite, bool $send = false) : bool{
		if($this->exists($invite)) return false;
		$this->invites[$invite->getAllyId()] = $invite;
		if($send) $invite->sync(AllianceInvite::SYNC_CREATE);
		return true;
	}

	public function exists($invite) : bool{
		$id = ($invite instanceof Gang ? $invite->getId() : $invite->getAllyId());

		return isset($this->invites[$id]);
	}

	public function getInvite($invite){
		$id = ($invite instanceof Gang ? $invite->getId() : $invite->getAllyId());
		return $this->invites[$id] ?? null;
	}

	public function getInviteById(int $id){
		return $this->invites[$id] ?? null;
	}

	public function removeInvite($invite, bool $send = false) : bool{
		if(!$this->exists($invite)) return false;
		$id = ($invite instanceof Gang ? $invite->getId() : $invite->getAllyId());
		unset($this->invites[$id]);
		if($send) $invite->sync(AllianceInvite::SYNC_DELETE);
		return true;
	}


}