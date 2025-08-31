<?php namespace prison;

use pocketmine\plugin\PluginBase;
use pocketmine\entity\Location;

use pocketmine\{
	inventory\CallbackInventoryListener,
	inventory\Inventory,
	item\Item,
	Server,
	player\Player
};

use prison\{
	block\BlockRegister,
	item\ItemRegister,

	auctionhouse\AuctionHouse,
	blocktournament\BlockTournament,
	cells\Cells,
	combat\Combat,
	data\Data,
	enchantments\Enchantments,
	gangs\Gangs,
	gangs\battle\BattleRequest,
	gangs\objects\Alliance,
	gangs\objects\AllianceInvite,
	gangs\objects\Gang,
	gangs\objects\GangInvite,
	gangs\objects\GangMember,
	grinder\Grinder,
	guards\Guards,
	hud\Hud,
	kits\Kits,
	koth\KOTH,
	leaderboards\Leaderboards,
	mines\Mines,
	mysteryboxes\MysteryBoxes,
	techits\Techits,
	quests\Quests,
	rankup\RankUp,
	shops\Shops,
	tags\Tags,
	trade\Trade,
	trash\Trash,
	vaults\Vaults
};
use prison\commands\{
	Spawn,
	Plots,
	Hangout,
	Gamemode,
	Grinder as GrinderCmd,
	Pay,
	Feed,
	GiveSpecial,
	OldPvP,

	SaveAllData,
};

use core\Core;
use core\user\User;
use core\utils\TextFormat;
use core\network\protocol\ServerSubUpdatePacket;
use core\network\server\SubServer;
use core\session\SessionManager;
use core\utils\{
	ShortcutCommand
};
use MyPlot\MyPlot;
use pocketmine\block\VanillaBlocks;
use pocketmine\inventory\PlayerInventory;
use pocketmine\utils\Config;
use pocketmine\world\WorldCreationOptions;
use prison\fishing\Fishing;
use prison\skills\Skills;


class Prison extends PluginBase{

	const GLOBAL = "global";
	const HERE = "here";

	const SPAWN_LEVEL = "newpsn";
	const SPAWN_LOCATION = [-805.5, 28, 383.5];

	public static ?self $instance = null;

	public SessionManager $sessionManager;

	public array $dj = []; //Double Jump

	public AuctionHouse $auctionhouse;
	public BlockTournament $blocktournament;
	public Cells $cells;
	public Combat $combat;
	public Data $data;
	public Enchantments $enchantments;
	public Fishing $fishing;
	public Gangs $gangs;
	public Grinder $grinder;
	public Guards $guards;
	public Hud $hud;
	public Kits $kits;
	public KOTH $koth;
	public Leaderboards $leaderboards;
	public Mines $mines;
	public MysteryBoxes $mysteryboxes;
	public Techits $techits;
	public Quests $quests;
	public RankUp $rankup;
	public Shops $shops;
	public Skills $skills;
	public Tags $tags;
	public Trash $trash;
	public Trade $trade;
	public Vaults $vaults;

	public function onEnable() : void{
		self::$instance = $this;

		$this->sessionManager = new SessionManager($this, PrisonSession::class, "prison_" . Core::thisServer()->getTypeId());

		$ts = Core::thisServer();

		foreach (['world', "s0plots", 'nether_plots_s0', 'end_plots_s0', 'koths', 'koths-new', 'garenas', 'Mines', 's1pvpremastered', 'newpsn'] as $worldName) {
			if (stristr($worldName, "plot") !== false && !($ts instanceof SubServer && $ts->getSubId() == "plots")) continue;
			if (($worldName == "s1pvpremastered" || stristr($worldName, "koth") !== false || stristr($worldName, "arena") !== false) && !($ts instanceof SubServer && $ts->getSubId() == "pvp")) continue;
			if(!$this->getServer()->getWorldManager()->isWorldGenerated($worldName)){
				if (stristr($worldName, "plot") !== false) {
					MyPlot::getInstance()->generateLevel($worldName);
				} else {
					$this->getServer()->getWorldManager()->generateWorld($worldName, WorldCreationOptions::create());
				}
			} else {
				$this->getServer()->getWorldManager()->loadWorld($worldName);
			}

			$world = $this->getServer()->getWorldManager()->getWorldByName($worldName);
			$world?->setAutoSave(true);
		}
		
		$lvl = $this->getServer()->getWorldManager()->getDefaultWorld();
		$lvl->setTime(0);
		$lvl->stopTime();
		$lvl->setAutoSave(true);

		//BlockRegister::init();

		$this->blocktournament = new BlockTournament($this);

		$this->cells = new Cells($this);
		$this->getCells()->getCellManager()->doFirstCache();

		$this->combat = new Combat($this);
		$this->data = new Data($this);
		$this->enchantments = new Enchantments($this);
		$this->fishing = new Fishing($this);

		$this->auctionhouse = new AuctionHouse($this);

		$this->gangs = new Gangs($this);
		$this->grinder = new Grinder($this);
		$this->guards = new Guards($this);
		$this->hud = new Hud($this);
		$this->kits = new Kits($this);
		$this->koth = new KOTH($this);
		$this->mines = new Mines($this);
		$this->mysteryboxes = new MysteryBoxes($this);
		$this->techits = new Techits($this);
		$this->quests = new Quests($this);
		$this->rankup = new RankUp($this);
		$this->shops = new Shops($this);
		// $this->skills = new Skills($this);
		$this->tags = new Tags($this);
		$this->trade = new Trade($this);
		$this->trash = new Trash($this);
		$this->vaults = new Vaults($this);

		$this->leaderboards = new Leaderboards($this);

		$this->getScheduler()->scheduleRepeatingTask(new TickTask($this), 1);
		$this->getServer()->getPluginManager()->registerEvents(new MainListener($this), $this);

		$cmdMap = $this->getServer()->getCommandMap();
		$cmdMap->registerAll("prison", [
			new Spawn($this, "spawn", "Teleport to spawn"),
			new Plots($this, "plots", "Open the plots menu"),
			new Hangout($this, "hangout", "Teleport to the Hangout"),
			new Gamemode($this, "gm", "Baba booey"),
			new GrinderCmd($this, "grinder", "Teleport to the Grinder"),
			new Pay($this, "pay", "Pay players!"),
			new Feed($this, "feed", "Fill your hunger bar (ranked)"),

			new OldPvP($this, "oldpvp", "Yes"),
			new GiveSpecial($this, "givespecial", "Gives special stuff"),

			new SaveAllData($this, "savealldata", "Save all data (tier 3)"),
		]);

		foreach([
			"gmc" => [
				"gamemode c",
				"fly"
			],
		] as $name => $shortcuts){
			$cmdMap->register("shortcut", new ShortcutCommand($this, $name, $shortcuts));
		}

		//$this->getServer()->getAsyncPool()->submitTask(new DataBackupTask());
		Core::getInstance()->getVote()->setupPrizes("prison");

		Core::getInstance()->getNetwork()->getServerManager()->addSubUpdateHandler(function(string $server, string $type, array $data) : void{
			switch($type){
				case "koth":
					$koth = $this->getKoth();
					$started = $data["started"] ?? false;
					$gameId = $data["gameId"] ?? 0;
					$message = $data["message"] ?? "";
					if($started){
						$koth->getGameById($gameId)->setActive();
						if($message !== "") Server::getInstance()->broadcastMessage($message);
					}else{
						$game = $koth->getGameById($gameId);
						if($game !== null && $game->isActive()){
							$game->end();
							if($message !== "") Server::getInstance()->broadcastMessage($message);
						}
					}
					break;

				case "getkoth":
					$games = [];
					foreach($this->getKoth()->getActiveGames() as $game)
						$games[] = $game->getId();

					(new ServerSubUpdatePacket([
						"server" => $server,
						"type" => "update",
						"data" => [
							"koth" => $games
						]
					]))->queue();
					break;

				case "update":
					if(!Core::thisServer()->isSubServer()){
						$games = [];
						foreach($this->getKoth()->getActiveGames() as $game)
							$games[] = $game->getId();

						(new ServerSubUpdatePacket([
							"server" => $server,
							"type" => "update",
							"data" => [
								"koth" => $games
							]
						]))->queue();
					}else{
						$koth = $data["koth"] ?? [];
						foreach($koth as $game){
							$this->getKoth()->getGameById($game)?->setActive();
						}
					}
					break;

				case "keyall":
					$type = $data["type"];
					$amount = $data["amount"];
					$colors = [
						"iron" => TextFormat::WHITE,
						"gold" => TextFormat::GOLD,
						"diamond" => TextFormat::AQUA,
						"emerald" => TextFormat::GREEN,
						"vote" => TextFormat::YELLOW,
						"divine" => TextFormat::RED,
					];
					/** @var PrisonPlayer $player */
					foreach($this->getServer()->getOnlinePlayers() as $player){
						if($player->isLoaded()){
							$player->sendMessage(TextFormat::GRAY . "Everyone online has received " . TextFormat::GREEN . "+" . $amount . " " . $colors[$type] . TextFormat::BOLD . strtoupper($type) . TextFormat::RESET . TextFormat::GREEN . " keys!");
							$session = $player->getGameSession()->getMysteryBoxes();
							$session->addKeys($type, $amount);
						}
					}
					break;

				case "gangRequest":
					$gm = $this->getGangs()->getGangManager();
					$gangs = array_map(function($gang) : int{
						return $gang->getId();
					}, $gm->getGangs());
					echo "received gang request! relaying " . count($gangs) . " gangs...", PHP_EOL;
					$servers = [];
					foreach(Core::thisServer()->getSubServers(false, true) as $server){
						$servers[] = $server->getIdentifier();
					}
					(new ServerSubUpdatePacket([
						"server" => $servers,
						"type" => "gangRequestResponse",
						"data" => ["gangs" => $gangs]
					]))->queue();
					break;

				case "gangRequestResponse":
					$gangs = $data["gangs"];
					echo "received gang request response! loading " . count($gangs) . " gangs...", PHP_EOL;
					$gm = $this->getGangs()->getGangManager();
					foreach($gangs as $gang){
						$gm->loadGang($gang);
					}
					break;

				case "gangFullSyncRequest":
					$gang = $data["gang"];
					if(
						($gng = $this->getGangs()->getGangManager()->getGangById($gang)) === null
					){
						echo "gang loaded via full sync", PHP_EOL;
						$this->getGangs()->getGangManager()->loadGang($gang);
					}else $gng->fullSyncSend($server);
					break;

				case "gangSync":
					$syncType = $data["type"];
					$gang = $data["gang"];
					if(($gng = ($gm = $this->getGangs()->getGangManager())->getGangById($gang)) === null) return;
					switch($syncType){
						case Gang::SYNC_MEMBER:
							$player = $data["player"];
							$member = $gng->getMemberByName($player);
							if($member !== null){
								if($member->getRole() !== ($role = $data["role"])){
									$pRole = $member->getRole();
									$member->setRole($role);
									$member->getPlayer()?->sendMessage(TextFormat::RI . "You have been " . ($pRole > $role ? "demoted" : "promoted") . " to " . $gng->getMemberManager()->getRoleName($role) . " in your gang!");
								}
								if($member->getKills() !== ($kills = $data["kills"])){
									$diff = $kills - $member->getKills();
									$member->kills = $kills;
									$gng->kills += $diff;
								}
								if($member->getDeaths() !== ($deaths = $data["deaths"])){
									$diff = $deaths - $member->getDeaths();
									$member->deaths = $deaths;
									$gng->deaths += $diff;
								}
								if($member->getBlocks() !== ($blocks = $data["blocks"])){
									$diff = $blocks - $member->getBlocks();
									$member->blocks = $blocks;
									$gng->blocks += $diff;
								}
							}
							break;
						case Gang::SYNC_MEMBER_CHANGE:
							$player = $data["player"];
							switch($data["change"]){
								case GangMember::SYNC_JOIN:
									Core::getInstance()->getUserPool()->useUser($player, function(User $user) use($data, $gng) : void{
										$member = new GangMember(
											$gng,
											$user,
											$data["role"],
											0, 0, 0, $data["joined"]
										);
										$gng->getMemberManager()->addMember($member);
										foreach($gng->getMemberManager()->getOnlineMembers() as $member){
											$member->getPlayer()?->sendMessage(TextFormat::GI . TextFormat::YELLOW . $user->getGamertag() . TextFormat::GRAY . " has joined your gang!");
										}
									});
									break;
								case GangMember::SYNC_LEAVE:
									$gng->getMemberManager()->removeMember($gng->getMemberByName($player)?->getUser()?->getXuid() ?? 0, true, false);
									foreach($gng->getMemberManager()->getOnlineMembers() as $member){
										$member->getPlayer()?->sendMessage(TextFormat::GI . TextFormat::YELLOW . $player . TextFormat::GRAY . " has left your gang!");
									}
									break;
							}
							break;
						case Gang::SYNC_ALLIANCE:
							$gangId = $data["gang"];
							$ally = $data["ally"];
							echo "received alliance sync " . $data["change"], PHP_EOL;
							var_dump($data);
							switch($data["change"]){
								case Alliance::SYNC_CREATE:
									$gng->getAllianceManager()->addAlliance($gangId, $ally, false, false);
									break;
								case Alliance::SYNC_DELETE:
									$gng->getAllianceManager()->removeAlliance($gangId, $ally, false);
									break;
							}
							break;
						case Gang::SYNC_ALLIANCE_INVITE:
							$to = $data["player"];
							echo "received alliance invite sync " . $data["change"], PHP_EOL;
							var_dump($data);
							switch($data["change"]){
								case AllianceInvite::SYNC_CREATE:
									Core::getInstance()->getUserPool()->useUser($data["player"], function(User $user) use($gng, $data) : void{
										$gng->getAllianceInviteManager()->addInvite(new AllianceInvite(
											$data["gang"], $data["ally"], $user, $data["message"]
										));
									});
									break;
								case AllianceInvite::SYNC_DELETE:
									if(($invite = $gng->getAllianceInviteManager()->getInviteById($data["ally"])) !== null)
										$gng->getAllianceInviteManager()->removeInvite($invite);
									break;
							}
							break;
						case Gang::SYNC_BATTLE_REQUEST:
							$requesting = $gm->getGangById($data["requesting"]);
							switch($data["change"]){
								case BattleRequest::SYNC_CREATE:
									$gng->getBattleRequestManager()->addRequest(new BattleRequest(
										$gng, $requesting,
										$gm->getBattleManager()->getKit($data["kit"]),
										$data["mode"], $data["max"]
									), false);
									$gng->getLeader()->getPlayer()?->sendMessage(TextFormat::YI . "Your gang has received a battle request from " . TextFormat::RED . $requesting->getName() . "! " . TextFormat::GRAY . "Type " . TextFormat::YELLOW . "/gang battle " . TextFormat::GRAY . "to view it!");
									break;
								case BattleRequest::SYNC_DELETE:
									$gng->getBattleRequestManager()->getRequestFrom($requesting)?->selfDestruct(false);
									break;
							}
							break;
						case Gang::SYNC_BATTLE_STATS:

							break;
						case Gang::SYNC_INVITE:
							$to = $data["player"];
							switch($data["change"]){
								case GangInvite::SYNC_CREATE:
									$from = $data["from"];
									Core::getInstance()->getUserPool()->useUsers([$to, $from], function(array $users) use($gng, $to, $from) : void{
										$gng->getInviteManager()->addInvite(new GangInvite(
											$gng,
											$users[$to],
											$users[$from]
										));
									});
									break;
								case GangInvite::SYNC_DELETE:
									Core::getInstance()->getUserPool()->useUser($to, function(User $user) use($gng) : void{
										$gng->getInviteManager()->removeInvite($user);
									});
									break;
							}
							break;
						case Gang::SYNC_GANG_DATA:
							foreach($data as $key => $value){
								switch($key){
									case "name":
										$gng->name = $value;
										break;
									case "description":
										$gng->description = $value;
										break;
									case "level":
										$gng->level = $value;
										break;
									case "trophies":
										$gng->trophies = $value;
										break;
									case "bank":
										$gng->bank = $value;
										break;
								}
							}
							break;
						case Gang::SYNC_GANG_DELETE:
							$gng->selfDestruct();
							break;
					}
					break;

				case "gangBattleSync":

					break;

				case "gangChat":
					$gang = $data["gang"];
					if(($gng = $this->getGangs()->getGangManager()->getGangById($gang)) === null) return;
					$player = $data["player"];
					$message = $data["message"];
					$msgType = $data["msgType"];
					if(($member = $gng->getMemberByName($player)) !== null){
						$gng->sendMessage($member->getUser(), $message, $msgType, false);
					}
					break;
			}
		});

		/** @var ServerInstance $ts */
		if(!($ts = Core::thisServer())->isSubServer()){
			(new ServerSubUpdatePacket([
				"server" => "prison-" . $ts->getTypeId() . "-pvp",
				"type" => "getkoth"
			]))->queue();
		}else{
			(new ServerSubUpdatePacket([
				"server" => "prison-" . $ts->getTypeId(),
				"type" => "getkoth"
			]))->queue();
		}
	}

	public function onDisable() : void{
		foreach($this->getServer()->getOnlinePlayers() as $player){
			/** @var PrisonPlayer $player */
			$player->gotoSpawn();
		}

		$this->getAuctionHouse()->close();
		$this->getCells()->close();
		$this->getMysteryBoxes()->close();
		$this->getTrade()->close();
		$this->getVaults()->close();
		$this->getGangs()->close();

		$this->getSessionManager()->close();
	}

	public function getSessionManager() : ?SessionManager{
		return $this->sessionManager;
	}

	public function saveAll(bool $async = true) : void{
		$this->getAuctionHouse()->getAuctionManager()->save();
		$this->getCells()->getCellManager()->saveAll($async);
		$this->getGangs()->getGangManager()->saveAll($async);
		$this->getSessionManager()->saveAll($async);
	}

	public static function getInstance() : self{
		return self::$instance;
	}

	public function getDatabase() : ?\mysqli{
		return $this->sessionManager->getDatabase();
	}

	public function getAuctionHouse() : AuctionHouse{
		return $this->auctionhouse;
	}

	public function getBlockTournament() : BlockTournament{
		return $this->blocktournament;
	}

	public function getCells() : Cells{
		return $this->cells;
	}

	public function getCombat() : Combat{
		return $this->combat;
	}

	public function getData() : Data{
		return $this->data;
	}

	public function getEnchantments() : Enchantments{
		return $this->enchantments;
	}

	public function getFishing() : Fishing{
		return $this->fishing;
	}

	public function getGangs() : Gangs{
		return $this->gangs;
	}

	public function getGrinder() : Grinder{
		return $this->grinder;
	}

	public function getGuards() : Guards{
		return $this->guards;
	}

	public function getHud() : Hud{
		return $this->hud;
	}

	public function getKits() : Kits{
		return $this->kits;
	}

	public function getKoth() : KOTH{
		return $this->koth;
	}

	public function getLeaderboards() : Leaderboards{
		return $this->leaderboards;
	}

	public function getMines() : Mines{
		return $this->mines;
	}

	public function getMysteryBoxes() : MysteryBoxes{
		return $this->mysteryboxes;
	}

	public function getTechits() : Techits{
		return $this->techits;
	}

	public function getQuests() : Quests{
		return $this->quests;
	}

	public function getRankUp() : RankUp{
		return $this->rankup;
	}

	public function getShops() : Shops{
		return $this->shops;
	}

	public function getSkills() : Skills{
		return $this->skills;
	}

	public function getTags() : Tags{
		return $this->tags;
	}

	public function getTrade() : Trade{
		return $this->trade;
	}

	public function getTrash() : Trash{
		return $this->trash;
	}

	public function getVaults() : Vaults{
		return $this->vaults;
	}

	/**
	 * Before session loads
	 */
	public function onPreJoin(Player $player) : void{
		$this->getCombat()->resetPlayer($player);
	}
	
	/**
	 * After session loads
	 */
	public function onJoin(Player $player) : void {
		$this->getCombat()->resetPlayer($player);
		/** @var PrisonPlayer $player */
		$player->getGameSession()->getData()->give();
		// $player->getSeeInv()?->addQueue(0);
		$this->getEnchantments()->calculateCache($player);

		$player->getArmorInventory()->getListeners()->add(new CallbackInventoryListener(function(Inventory $inventory, int $slot, Item $oldItem) : void{
			/** @var PlayerInventory $inventory */
			$this->getEnchantments()->calculateCache($inventory->getHolder());
		}, null));

		$this->getBlockTournament()->onJoin($player);
		$this->getGangs()->getGangManager()->loadByPlayer($player);
		$this->getLeaderboards()->onJoin($player);
		$this->getMysteryBoxes()->onJoin($player);
		
		$this->getHud()->send($player);

		// Ignore this shane, I love u - Jay
		if ($player->getUser()->getXuid() == 2535417851980714) $player->getGameSession()->getTags()->addTag($this->tags->getTag("CaliKid"));
		if ($player->getUser()->getXuid() == 2535472340917821) $player->getGameSession()->getTags()->addTag($this->tags->getTag("Reformed>AT"));
		if ($player->getUser()->getXuid() == 2535420710653774) $player->getGameSession()->getTags()->addTag($this->tags->getTag("Ploogerrag"));
		if ($player->getUser()->getXuid() == 2535422608590078) $player->getGameSession()->getTags()->addTag($this->tags->getTag("crayon"));
	}

	/**
	 * Before session saves
	 */
	public function onQuit(Player $player, bool $partial = false) : void {
		/** @var PrisonPlayer $player */
		if(Core::getInstance()->getNetwork()->isShuttingDown()) return;

		if(!$player->isLoaded()) return;

		$this->getTrade()->onQuit($player);
		if($partial) return;
		
		$this->getLeaderboards()->onQuit($player);

		if(($combat = $player->getGameSession()->getCombat())->inPvPMode()){
			$combat->togglePvPMode();
		}
		if($combat->isTagged()){
			$combat->punish();
		}

		$this->getGangs()->getGangManager()->onLeave($player);

		($data = $player->getGameSession()->getData())->update();
	}

	public function isTestServer() : bool{
		return in_array($this->getServer()->getPort(), [1004, 1008, 1009]);
	}

	/////
	public static function getSpawn() : Location{
		return new Location(self::SPAWN_LOCATION[0], self::SPAWN_LOCATION[1], self::SPAWN_LOCATION[2], Server::getInstance()->getWorldManager()->getWorldByName(self::SPAWN_LEVEL), 90, 0);
	}
 
}