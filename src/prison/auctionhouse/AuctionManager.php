<?php namespace prison\auctionhouse;

use pocketmine\player\Player;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\item\Item;

use prison\Prison;

use core\Core;
use core\session\mysqli\data\{
	MySqlRequest,
	MySqlQuery
};
use core\utils\conversion\LegacyItemIds;

class AuctionManager{

	const SAVE_DELAY = 600;

	public static int $auctionId = 0;
	public int $lastSave = 0;

	public array $auctions = [];

	public function __construct(){
		if(!Core::thisServer()->isSubServer()) $this->load();
		//$this->rewardAll();
	}

	public function getLastSave() : int{
		return $this->lastSave;
	}

	public function setLastSave() : void{
		$this->lastSave = time();
	}

	public function canAutoSave() : bool{
		return $this->getLastSave() + self::SAVE_DELAY - time() < 0;
	}

	public function tick() : void{
		foreach($this->getAuctions() as $id => $auction){
			if(!$auction->tick()){
				$auction->reward();
				$this->removeAuction($auction);
			}
		}
		if($this->canAutoSave()){
			$this->save(true);
			$this->setLastSave();
		}
	}

	public function close() : void{
		$this->save();
		unset($this->auctions);
	}

	public function load() : void{
		Prison::getInstance()->getSessionManager()->sendStrayRequest(new MySqlRequest("load_auctions", new MySqlQuery("main", "SELECT * FROM auctions")), function(MySqlRequest $request) : void{
			$rows = $request->getQuery()->getResult()->getRows();
			$xuids = [];
			foreach($rows as $auction){
				if(!in_array($auction["xuid"], $xuids)) $xuids[] = $auction["xuid"];
				if(!in_array($auction["bidder"], $xuids) && $auction["bidder"] !== Auction::NO_BIDDER) $xuids[] = $auction["bidder"];
			}
			Core::getInstance()->getUserPool()->useUsers($xuids, function(array $users) use($rows) : void{
				$count = 0;
				foreach($rows as $auction){
					$id = self::$auctionId++;
					$this->auctions[$id] = new Auction($id, $users[$auction["xuid"]], $auction["created"], $auction["name"], $this->getItemFromData($auction["item"]), $auction["startingbid"], $auction["buynow"], ($auction["bidder"] == Auction::NO_BIDDER ? null : $users[$auction["bidder"]]), $auction["bid"]);
					$count++;
				}
				echo $count . " auctions setup!", PHP_EOL;
			});
		});
	}

	public function rewardAll() : void{
		foreach($this->getAuctions() as $auction){
			$auction->reward();
			$this->removeAuction($auction);
		}
	}

	/**
	 * @return Auction[]
	 */
	public function getAuctions() : array{
		return $this->auctions;
	}
	
	/**
	 * Checks if auctions expired and removes them from list
	 */
	public function validateAuctions(array $auctions) : array{
		foreach($auctions as $key => $auction){
			if($this->getAuctionByAuction($auction) === null) unset($auctions[$key]);
		}
		return $auctions;
	}

	public function getAuction(int $id) : ?Auction{
		return $this->auctions[$id] ?? null;
	}

	public function getAuctionByAuction(Auction $auction) : ?Auction{
		return $this->getAuction($auction->getId());
	}

	/**
	 * @return Auction[]
	 */
	public function getPlayerAuctions($player) : array{ //todo: fix better
		if($player instanceof Player) $player = $player->getName();
		$player = strtolower($player);
		$auctions = [];
		foreach($this->getAuctions() as $auction){
			if(strtolower($auction->getOwner()->getGamertag()) == $player) $auctions[] = $auction;
		}
		return $auctions;
	}

	public function addAuction(Auction $auction, bool $save = false) : void{
		$this->auctions[$auction->getId()] = $auction;
		if($save) $this->save(true);
	}

	public function removeAuction(Auction $auction, bool $delete = true) : void{
		if($delete) $auction->delete();

		if(isset($this->auctions[$auction->getId()]))
			unset($this->auctions[$auction->getId()]);
	}

	public function getItemFromData($data){
		$data = unserialize(base64_decode($data));
		if($data instanceof CompoundTag){
			return Item::nbtDeserialize($data);
		}elseif($data instanceof Item){
			return $data;
		}
		return null;
	}

	public function getTotalPages(array $auctions = []) : int{
		$pages = array_chunk($auctions, 10);
		return count($pages);
	}

	public function getPage(array $auctions, int $page) : array{
		$pages = array_chunk($auctions, 10);
		return $pages[$page - 1] ?? [];
	}

	public function hasNextPage(array $auctions, int $page) : bool{
		$count = count($this->getPage($auctions, $page));
		return $count == 10 && count($auctions) > $page * 10;
	}

	public function hasBackPage(array $auctions, int $page) : bool{
		return $page > 1;
	}

	public function getAuctionsBySearch(array $criteria) : array{
		$auctions = $this->getAuctions();

		$opt = strtolower($criteria["by"]);
		if($opt != ""){
			$auctions = $this->getPlayerAuctions($opt);
		}

		$opt = strtolower($criteria["name"]);
		if($opt != ""){
			foreach($auctions as $key => $auction){
				similar_text(($st1 = strtolower($opt)), ($st2 = strtolower($auction->getName())), $pc);
				if($st1 != $st2 && $pc <= 75){
					unset($auctions[$key]);
				}
			}
		}

		$opt = $criteria["item"];
		$int = (int) $opt;
		if($opt != ""){
			if($int > 0){
				foreach($auctions as $key => $auction){
					if($auction->getItem()->getTypeId() != $opt){
						unset($auctions[$key]);		
					}
				}
			}else{
				foreach($auctions as $key => $auction){
					similar_text(strtolower($opt), strtolower($auction->getItem()->getName()), $pc);
					if($pc <= 50){
						unset($auctions[$key]);
					}
				}
			}
		}

		$opt = $criteria["enchanted"];
		foreach($auctions as $key => $auction){
			if($auction->getItem()->hasEnchantments() != $opt){
				unset($auctions[$key]);
			}
		}

		$opt = $criteria["sortby"];
		if($opt != 0){
			if($opt == 1 || $opt == 2){
				if($opt == 1 || $opt == 3 || $opt == 5){
					usort($auctions, function($a, $b){
						if($a->getBid() < $b->getBid()){
							return -1;
						}else{
							return 1;
						}
					});
				}else{
					usort($auctions, function($a, $b){
						if($a->getBid() > $b->getBid()){
							return -1;
						}else{
							return 1;
						}
					});
				}
			}elseif($opt == 3 || $opt == 4){
				if($opt == 1 || $opt == 3 || $opt == 5){
					usort($auctions, function($a, $b){
						if($a->getBuyNowPrice() < $b->getBuyNowPrice()){
							return -1;
						}else{
							return 1;
						}
					});
				}else{
					usort($auctions, function($a, $b){
						if($a->getBuyNowPrice() > $b->getBuyNowPrice()){
							return -1;
						}else{
							return 1;
						}
					});
				}
			}elseif($opt == 5 || $opt == 6){
				if($opt == 1 || $opt == 3 || $opt == 5){
					usort($auctions, function($a, $b){
						if($a->getCreated() < $b->getCreated()){
							return -1;
						}else{
							return 1;
						}
					});
				}else{
					usort($auctions, function($a, $b){
						if($a->getCreated() > $b->getCreated()){
							return -1;
						}else{
							return 1;
						}
					});
				}
			}
		}
		return $auctions;
	}

	public function save(bool $async = false) : void{
		if($async){
			$request = new MySqlRequest("save_auctions", []);
			foreach($this->getAuctions() as $auction){
				$request->addQuery(new MySqlQuery(
					"save_auction_" . $auction->getOwner()->getXuid() . "_" . $auction->getCreated(),
					"INSERT INTO auctions(
						xuid, created, name, item, startingbid, buynow, bidder, bid
					) VALUES(?, ?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE
						name=VALUES(name), item=VALUES(item),
						startingbid=VALUES(startingbid), buynow=VALUES(buynow),
						bidder=VALUES(bidder), bid=VALUES(bid)",
					[
						$auction->getOwner()->getXuid(),
						$auction->getCreated(),
						$auction->getName(),
						$auction->getEncodedItem(),
						$auction->getStartingBid(),
						$auction->getBuyNowPrice(),
						$auction->getBidder() !== null ? $auction->getBidder()->getXuid() : Auction::NO_BIDDER,
						$auction->getBid()
					]
				));
			}
			Prison::getInstance()->getSessionManager()->sendStrayRequest($request, function(MySqlRequest $request) : void{});
			return;
		}
		$db = Prison::getInstance()->getSessionManager()->getDatabase();
		$stmt = $db->prepare(
			"INSERT INTO auctions(
				xuid, created, name, item, startingbid, buynow, bidder, bid
			) VALUES(?, ?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE
				name=VALUES(name), item=VALUES(item),
				startingbid=VALUES(startingbid), buynow=VALUES(buynow),
				bidder=VALUES(bidder), bid=VALUES(bid)"
		);
		foreach($this->getAuctions() as $auction){
			$xuid = $auction->getOwner()->getXuid();
			$created = $auction->getCreated();
			$name = $auction->getName();
			$item = $auction->getEncodedItem();
			$starting = $auction->getStartingBid();
			$buynow = $auction->getBuyNowPrice();
			$bidder = ($auction->getBidder() !== null ? $auction->getBidder()->getXuid() : Auction::NO_BIDDER);
			$bid = $auction->getBid();

			$stmt->bind_param("iissiiii", $xuid, $created, $name, $item, $starting, $buynow, $bidder, $bid);
			$stmt->execute();
		}
		$stmt->close();
	}

}