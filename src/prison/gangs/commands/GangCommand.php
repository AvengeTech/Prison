<?php namespace prison\gangs\commands;

use pocketmine\Server;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\plugin\Plugin;

use prison\Prison;
use prison\PrisonPlayer;
use prison\gangs\GangManager;
use prison\gangs\objects\{
	Gang,
	GangInvite,
	GangMember
};
use prison\gangs\uis\{
	CreateGangUi,
	ConfirmLeaveGangUi,
	ConfirmDeleteGangUi,

	GangDescriptionUi,
	GangInfoUi,

	InviteMemberUi,
	InvitesManagerUi,

	BankManagerUi,

	PromoteMemberUi,
	DemoteMemberUi,
	KickMemberUi,

	UpgradeGangUi,

	alliance\AllianceMainUi,

	shop\GangShopUi,

	battle\GangBattleUi,
	battle\BattleRequestsUi,
	battle\OngoingBattlesUi,
	battle\BattleStatsUi,

	CommandHelpUi
};

use core\Core;
use core\utils\TextFormat;

class GangCommand extends Command{

	public function __construct(public Prison $plugin, string $name, string $description){
		parent::__construct($name, $description);
		$this->setPermission("prison.perm");
		$this->setAliases(["gang", "gg", "g"]);
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args) {
		/** @var PrisonPlayer $sender */
		if($sender instanceof Player){
			$gangs = $this->plugin->getGangs();
			$gm = $gangs->getGangManager();
			$option = strtolower((array_shift($args) ?? "none"));
			switch($option){
				case "create":
					if($gm->inGang($sender)){
						$sender->sendMessage(TextFormat::RI . "You are already in a gang!");
						return;
					}

					if ($gm->hasLeftGang($sender) && !$sender->isTier3()) {
						$sender->sendMessage(TextFormat::RI . "You have recently left a gang! You cannot create another one for at least 2 hours");
						return;
					}

					if ($sender->getTechits() < ($price = GangManager::GANG_PRICE) && !$sender->isTier3()) {
						$sender->sendMessage(TextFormat::RI . "You must have at least " . TextFormat::AQUA . number_format($price) . " Techits " . TextFormat::GRAY . "to create a gang!");
						return;
					}
					$sender->showModal(new CreateGangUi($sender, $args[0] ?? "My Gang", $args[1] ?? "Join my gang!"));
					break;

				case "leave":
					if(!$gm->inGang($sender)){
						$sender->sendMessage(TextFormat::RI . "You are not in a gang!");
						return;
					}
					$gang = $gm->getPlayerGang($sender);
					$member = $gang->getMemberManager()->getMember($sender);
					$role = $member->getRole();
					if($role == GangMember::ROLE_LEADER){
						$sender->sendMessage(TextFormat::RI . "You cannot leave a gang you own!");
						return;
					}

					$sender->showModal(new ConfirmLeaveGangUi($sender));
					break;

				case "delete":
				case "del":
				case "genocide":
					if(!$gm->inGang($sender)){
						$sender->sendMessage(TextFormat::RI . "You are not in a gang!");
						return;
					}
					$gang = $gm->getPlayerGang($sender);
					$member = $gang->getMemberManager()->getMember($sender);
					$role = $member->getRole();
					if ($role < GangMember::ROLE_LEADER && !$sender->isTier3()) {
						$sender->sendMessage(TextFormat::RI . "You must be a gang elder to use this subcommand!");
						return;
					}

					$sender->showModal(new ConfirmDeleteGangUi($sender));
					break;

				case "description":
				case "d":
					if(!$gm->inGang($sender)){
						$sender->sendMessage(TextFormat::RI . "You must be in a gang to use this subcommand!");
						return;
					}
					$gang = $gm->getPlayerGang($sender);
					$member = $gang->getMemberManager()->getMember($sender);
					$role = $member->getRole();
					if($role < GangMember::ROLE_CO_LEADER && !$sender->isTier3()){
						$sender->sendMessage(TextFormat::RI . "You must be a gang co-leader to use this subcommand!");
						return;
					}
					$sender->showModal(new GangDescriptionUi($sender));
					break;

				case "info":
				case "details":
				case "stats":
					if(!$gm->inGang($sender)){
						$sender->sendMessage(TextFormat::RI . "You must be in a gang to use this subcommand!");
						return;
					}
					if(count($args) == 0){
						$gang = $gm->getPlayerGang($sender);
						$sender->showModal(new GangInfoUi($sender, $gang));
						break;
					}
					$name = array_shift($args);
					$gang = $gm->getGangByName($name);
					if($gang === null){
						$gm->loadByName($name, function(?Gang $gang) use($sender) : void{
							if(!$sender->isConnected()) return;
							if($gang === null){
								$sender->sendMessage(TextFormat::RI . "Gang by this name does not exist!");
								return;
							}
							$sender->showModal(new GangInfoUi($sender, $gang));
						});
						return;
					}
					if($gang === null){
						$sender->sendMessage(TextFormat::RI . "Gang by this name does not exist!");
						return;
					}
					$sender->showModal(new GangInfoUi($sender, $gang));
					break;

				case "pinfo":
				case "player":
					if(empty($args)){
						$sender->sendMessage(TextFormat::RI . "Usage: /" . $commandLabel . " player <player>");
						return;
					}
					$player = Server::getInstance()->getPlayerByPrefix(array_shift($args));
					if(!$player instanceof Player){
						$sender->sendMessage(TextFormat::RI . "Player not online!");
						return;
					}
					if(!$gm->inGang($player)){
						$sender->sendMessage(TextFormat::RI . "This player is not in a gang!");
						return;
					}
					$gang = $gm->getPlayerGang($player);
					$sender->showModal(new GangInfoUi($sender, $gang));
					break;

				case "invite":
				case "inv":
					if(!$gm->inGang($sender)){
						$sender->sendMessage(TextFormat::RI . "You must be in a gang to use this subcommand!");
						return;
					}
					$gang = $gm->getPlayerGang($sender);
					$member = $gang->getMemberManager()->getMember($sender);
					$role = $member->getRole();
					if($role < GangMember::ROLE_ELDER && !$sender->isTier3()){
						$sender->sendMessage(TextFormat::RI . "You must be a gang elder to use this subcommand!");
						return;
					}

					if(count($gang->getMemberManager()->getMembers()) >= $gang->getMaxMembers()&& !$sender->isTier3()){
						$sender->sendMessage(TextFormat::RI . "Your gang is full!");
						return;
					}

					if(empty($args)){
						$sender->showModal(new InviteMemberUi($sender));
						return;
					}

					/** @var PrisonPlayer $pi */
					$pi = Server::getInstance()->getPlayerByPrefix(array_shift($args));
					if(!$pi instanceof Player || !$pi->isLoaded()){
						$sender->sendMessage(TextFormat::RI . "Player not online! Cannot invite");
						return;
					}
					if($gm->inGang($pi)){
						$sender->sendMessage(TextFormat::RI . "This player is already in a gang!");
						return;
					}

					$im = $gang->getInviteManager();
					if($im->exists($pi)){
						$sender->sendMessage(TextFormat::RI . "An invitation has already been sent to this player!");
						return;
					}
					$inv = new GangInvite($gang, $pi->getUser(), $sender->getUser());

					$sender->sendMessage(TextFormat::GI . "Sent a gang invite to " . TextFormat::AQUA . $pi->getName() . TextFormat::GRAY . "! It will expire in " . TextFormat::YELLOW . "60" . TextFormat::GRAY . " seconds");
					$im->addInvite($inv);
					break;

				case "invites":
					if($gm->inGang($sender)){
						$sender->sendMessage(TextFormat::RI . "You are already in a gang!");
						return;
					}

					if($gm->hasLeftGang($sender)){
						$sender->sendMessage(TextFormat::RI . "You have recently left a gang! You cannot join another one for at least 2 hours");
						return;
					}

					if(count($gm->getPlayerInvites($sender)) == 0){
						$sender->sendMessage(TextFormat::RI . "You have not been invited to any gangs!");
						return;
					}
					$sender->showModal(new InvitesManagerUi($sender));
					break;

				case "kick":
					if(!$gm->inGang($sender)){
						$sender->sendMessage(TextFormat::RI . "You must be in a gang to use this subcommand!");
						return;
					}
					$gang = $gm->getPlayerGang($sender);
					$member = $gang->getMemberManager()->getMember($sender);
					$role = $member->getRole();
					if($role < GangMember::ROLE_ELDER){
						$sender->sendMessage(TextFormat::RI . "You must be a gang elder to use this subcommand!");
						return;
					}

					if(count($args) == 0){
						$sender->showModal(new KickMemberUi($sender));
						return;
					}
					break;

				case "promote":
					if(!$gm->inGang($sender)){
						$sender->sendMessage(TextFormat::RI . "You must be in a gang to use this subcommand!");
						return;
					}
					$gang = $gm->getPlayerGang($sender);
					$member = ($mm = $gang->getMemberManager())->getMember($sender);
					$role = $member->getRole();
					if($role < GangMember::ROLE_CO_LEADER && !$sender->isTier3()){
						$sender->sendMessage(TextFormat::RI . "You must be at least a gang co-leader to use this subcommand!");
						return;
					}
					if(count($args) == 0){
						$sender->showModal(new PromoteMemberUi($sender));
						return;
					}

					break;
				case "demote":
					if(!$gm->inGang($sender)){
						$sender->sendMessage(TextFormat::RI . "You must be in a gang to use this subcommand!");
						return;
					}
					$gang = $gm->getPlayerGang($sender);
					$member = $gang->getMemberManager()->getMember($sender);
					$role = $member->getRole();
					if($role < GangMember::ROLE_CO_LEADER && !$sender->isTier3()){
						$sender->sendMessage(TextFormat::RI . "You must be at least a gang co-leader to use this subcommand!");
						return;
					}

					if(count($args) == 0){
						$sender->showModal(new DemoteMemberUi($sender));
						return;
					}

					break;

				case "chat":
				case "c":
					if(!$gm->inGang($sender)){
						$sender->sendMessage(TextFormat::RI . "You must be in a gang to use this subcommand!");
						return;
					}
					if(empty($args)){
						$sender->sendMessage(TextFormat::RI . "Usage: chat [gang,g:ally,a:off]");
						return;
					}
					$gang = $gm->getPlayerGang($sender);
					$member = $gang->getMemberManager()->getMember($sender);
					switch(strtolower(array_shift($args))){
						default:
							$sender->sendMessage(TextFormat::RI . "Usage: chat [gang,g:ally,a:o,n,off]");
							break;
						case "0":
						case "off":
						case "o":
						case "n":
							$member->setChatMode(0);
							$sender->sendMessage(TextFormat::GI . "Gang chat disabled!");
							break;
						case "1":
						case "gang":
						case "g":
							$member->setChatMode(1);
							$sender->sendMessage(TextFormat::GI . "Gang chat enabled!");
							break;
						case "2":
						case "ally":
						case "a":
							$member->setChatMode(2);
							$sender->sendMessage(TextFormat::GI . "Gang alliance chat enabled!");
							break;
					}
					break;

				case "bank":
					if(!$gm->inGang($sender)){
						$sender->sendMessage(TextFormat::RI . "You must be in a gang to use this subcommand!");
						return;
					}
					$gang = $gm->getPlayerGang($sender);

					if(Core::thisServer()->isSubServer()){
						$sender->sendMessage(TextFormat::RI . "Gang bank can only be accessed from lobby!");
						return;
					}

					if(count($args) == 0){
						$sender->showModal(new BankManagerUi($sender));
						return;
					}
					switch(strtolower(array_shift($args))){
						case "deposit":
							if(empty($args)){
								$sender->sendMessage(TextFormat::RI . "You forgot to enter an amount!");
								return;
							}
							$amount = (int)array_shift($args);
							if($amount <= 0){
								$sender->sendMessage(TextFormat::RI . "Amount must be more than 0!");
								return;
							}
							if($amount > $sender->getTechits()){
								$sender->sendMessage(TextFormat::RI . "You do not have this many techits to deposit!");
								return;
							}
							$gang->addToBank($amount, $sender);
							$sender->sendMessage(TextFormat::GI . "Successfully deposited " . TextFormat::AQUA . number_format($amount) . " techits " . TextFormat::GRAY . "into your gang's bank!");
							break;
						case "withdraw":
							if($gang->getRole($sender) < GangMember::ROLE_CO_LEADER && !$sender->isTier3()){
								$sender->sendMessage(TextFormat::RI . "You must be a gang co-leader to withdraw gang cash!");
								return;
							}
							if(empty($args)){
								$sender->sendMessage(TextFormat::RI . "You forgot to enter an amount!");
								return;
							}
							$amount = (int)array_shift($args);
							if($amount <= 0){
								$sender->sendMessage(TextFormat::RI . "Amount must be more than 0!");
								return;
							}
							if($amount > ($balance = $gang->getBankValue())){
								$sender->sendMessage(TextFormat::RI . "Your bank does not hold this many techits! Balance: " . TextFormat::AQUA . number_format($balance));
								return;
							}
							$gang->takeFromBank($amount, $sender);
							$sender->sendMessage(TextFormat::GI . "Successfully withdrew " . TextFormat::AQUA . number_format($amount) . " techits " . TextFormat::GRAY . "from your gang's bank!");
							break;
					}
					break;

				case "upgrade":
				case "u":
					if(!$gm->inGang($sender)){
						$sender->sendMessage(TextFormat::RI . "You must be in a gang to use this subcommand!");
						return;
					}
					$gang = $gm->getPlayerGang($sender);
					$member = $gang->getMemberManager()->getMember($sender);
					$role = $member->getRole();
					if($role < GangMember::ROLE_CO_LEADER && !$sender->isTier3()){
						$sender->sendMessage(TextFormat::RI . "You must be gang leader to use this subcommand!");
						return;
					}

					if($gang->getLevel() >= $gang::MAX_LEVEL){
						$sender->sendMessage(TextFormat::RI . "Your gang is already the max level and can no longer be upgraded!");
						return;
					}
					if(!$gang->canLevelUp() && !$sender->isTier3()){
						$c = $gang::LEVEL_CHART[min(max(1, $gang->getLevel() + 1), 5)];
						$sender->sendMessage(TextFormat::RI . "Your gang does not meet the requirements to upgrade! Trophies needed: " . ($gang->getTrophies() >= $c["trophies"] ? TextFormat::AQUA : TextFormat::RED) . number_format($c["trophies"]) . TextFormat::GRAY . ", Techits needed: " . ($gang->getBankValue() >= $c["techits"] ? TextFormat::AQUA : TextFormat::RED) . number_format($c["techits"]));
						return;
					}

					if(Core::thisServer()->isSubServer()){
						$sender->sendMessage(TextFormat::RI . "Gang can only be leveled up from lobby!");
						return;
					}

					if(count($args) == 0){
						$sender->showModal(new UpgradeGangUi($sender));
						return;
					}
					break;

				case "shop":
				case "market":
					if(!$gm->inGang($sender)){
						$sender->sendMessage(TextFormat::RI . "You must be in a gang to use this subcommand!");
						return;
					}
					$gang = $gm->getPlayerGang($sender);
					$member = $gang->getMemberManager()->getMember($sender);
					$role = $member->getRole();
					if($role < GangMember::ROLE_ELDER && !$sender->isTier3()){
						$sender->sendMessage(TextFormat::RI . "You must be at least a gang elder to use this subcommand!");
						return;
					}

					$sender->showModal(new GangShopUi($sender, $gang));
					break;

				case "a":
				case "ally":
				case "alliance":
				case "allies":
				case "alliances":
					if(!$gm->inGang($sender)){
						$sender->sendMessage(TextFormat::RI . "You must be in a gang to use this subcommand!");
						return;
					}
					$gang = $gm->getPlayerGang($sender);

					if(count($args) == 0){
						$sender->showModal(new AllianceMainUi($sender));
						return;
					}
					break;

				case "battle":
				case "b":
					$sender->sendMessage(TextFormat::RI . "Gang battles are temporarily disabled!");
					return;
					
					if(!$gm->inGang($sender)){
						$sender->sendMessage(TextFormat::RI . "You must be in a gang to use this subcommand!");
						return;
					}
					if(Prison::getInstance()->getBlockTournament()->getGameManager()->inGame($sender)){
						$sender->sendMessage(TextFormat::RI . "You cannot join a battle while in a block tournament!");
						return;
					}
					$gang = $gm->getPlayerGang($sender);
					$bm = $gm->getBattleManager();
					if($bm->inBattle($gang)){
						$sender->showModal(new GangBattleUi($sender, $gang));
					}else{
						if(!$gang->isLeader($sender)){
							$sender->sendMessage(TextFormat::RI . "Your gang is not currently in a battle! Only gang leaders can manage battle requests.");
							return;
						}
						$sender->showModal(new BattleRequestsUi($sender, $gang));
					}
					break;

				case "battles":
				case "spectate":
					$gang = $gm->getPlayerGang($sender);
					$bm = $gm->getBattleManager();
					if($sender->isBattleParticipant()){
						$sender->sendMessage(TextFormat::RI . "You cannot spectate a battle while in a battle!");
						return;
					}
					if(Prison::getInstance()->getBlockTournament()->getGameManager()->inGame($sender)){
						$sender->sendMessage(TextFormat::RI . "You cannot spectate a battle while in a block tournament!");
						return;
					}
					if(empty($bm->getBattles(true))){
						$sender->sendMessage(TextFormat::RI . "There are no ongoing battles.");
						return;
					}
					$sender->showModal(new OngoingBattlesUi($sender));
					break;

				case "results":
				case "r":
					if(!$gm->inGang($sender)){
						$sender->sendMessage(TextFormat::RI . "You must be in a gang to use this subcommand!");
						return;
					}
					$gang = $gm->getPlayerGang($sender);
					if(count($gang->getBattleStatManager()->getRecentBattleStats()) <= 0){
						$sender->sendMessage(TextFormat::RI . "Your gang has no recent battles!");
						return;
					}
					$sender->showModal(new BattleStatsUi($sender, $gang));
					break;

				case "givebattlekit":
				case "gbk":
					if(!$sender->isTier3() && !Prison::getInstance()->isTestServer()){
						$sender->sendMessage(TextFormat::RI . "You cannot use this subcommand!");
						return;
					}

					if(empty($args)){
						$sender->sendMessage(TextFormat::RI . "Usage: /gang gbk <id:name>");
						return;
					}
					$bm = $gm->getBattleManager();
					$id = strtolower(array_shift($args));
					$kit = null;
					$kitnames = [];
					foreach($bm->getKits() as $k){
						$kitnames[$k->getId()] = $k->getName();
						if($k->getId() == $id || strtolower($k->getName()) == $id){
							$kit = $k;
							break;
						}
					}
					if($kit === null){
						$sender->sendMessage(TextFormat::RI . "Invalid kit! Available kits:");
						foreach($kitnames as $id => $name)
							$sender->sendMessage(TextFormat::GRAY . "- " . TextFormat::YELLOW . $name . " (" . $id . ")");

						return;
					}

					$kit->equip($sender);
					$sender->sendMessage(TextFormat::GI . "Equipped the " . TextFormat::YELLOW . $kit->getName() . TextFormat::GRAY . " battle kit!");
					break;

				case "addtrophies":
					if(!$sender->isTier3() && !Prison::getInstance()->isTestServer()){
						$sender->sendMessage(TextFormat::RI . "You cannot use this subcommand!");
						return;
					}
					if(!$gm->inGang($sender)){
						$sender->sendMessage(TextFormat::RI . "You must be in a gang to use this subcommand!");
						return;
					}
					$gang = $gm->getPlayerGang($sender);
					if(empty($args)){
						$sender->sendMessage(TextFormat::RI . "Usage: /gang addtrophies <amount>");
						return;
					}
					$amt = (int)array_shift($args);
					if($amt <= 0){
						$sender->sendMessage(TextFormat::RI . "Amount must be at least 1!");
						return;
					}
					$gang->addTrophies($amt);
					$sender->sendMessage(TextFormat::RI . "Your gang now has " . TextFormat::GOLD . $gang->getTrophies() . " trophies!");
					break;
				default:
				case "help":
					$sender->showModal(new CommandHelpUi($sender));
					break;
			}
		}
	}

	public function getPlugin() : Plugin{
		return $this->plugin;
	}

}