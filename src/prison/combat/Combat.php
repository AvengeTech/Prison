<?php namespace prison\combat;

use pocketmine\Server;
use pocketmine\entity\{
	Entity,
	projectile\Projectile
};
use pocketmine\event\{
	entity\EntityDamageByEntityEvent
};
use pocketmine\item\Tool;
use pocketmine\player\{
	GameMode,
	Player
};

use prison\Prison;
use prison\PrisonPlayer;
use prison\combat\command\{
	BountyCommand,
	PvP
};
use prison\gangs\battle\Battle;
use prison\gangs\objects\TrophyData;
use prison\guards\entity\Guard;
use prison\guards\Bin;
use prison\quests\Structure as QuestIds;

use core\Core;
use core\techie\TechieBot as Techie;
use core\utils\TextFormat;

class Combat{

	const KILL_MESSAGES = [
		"Nice kill, {name}!",
		"Woah.. That was a brutal kill!",
		"Wow! Sick weapon skills dude!",
		"{name}, your skills are legendary!",
	];

	const DEATH_MESSAGES = [
		"Ouch. That looks like it hurt.",
		"Oof. Better luck next time.",
		"Oh no! You died!",
		"I believe in you next time, {name}!",
	];
	
	const WHITELISTED_COMMANDS = [
		"g c", "g c o", "g c a", "g c g",
		"g c off", "g c ally", "g c gang",
		"gang chat",
		"gang chat o", "gang chat off",
		"gang chat a", "gang chat ally",
		"gang chat g", "gang chat gang", //lots of fuckin alias combos xd

		"settings",

		// staff
		"staffchat", "stp",
		"gamemode", "vanish"
	];

	public function __construct(public Prison $plugin){
		$plugin->getServer()->getCommandMap()->registerAll("combat", [
			new BountyCommand($plugin, "bounty", "Open bounty menu"),
			new PvP($plugin, "pvp", "Enter PvP mode (WARNING: Players will be able to hit you)")
		]);
	}

	public function close() : void{
		foreach(Server::getInstance()->getOnlinePlayers() as $player){
			/** @var PrisonPlayer $player */
			if($player->hasGameSession()){
				$player->getGameSession()->getCombat()->disableTag();
			}
		}
	}

	public function getBounties() : array{
		$bounties = [];
		foreach(Server::getInstance()->getOnlinePlayers() as $player) {
			/** @var PrisonPlayer $player */
			if($player->hasGameSession() && ($session = $player->getGameSession()->getCombat())->hasBounty()){
				$bounties[$player->getName()] = $session->getBountyValue();
			}
		}
		return $bounties;
	}

	public function canCombat(Player $player, mixed $entity = null) : bool {
		/** @var PrisonPlayer $player */
		/** @var PrisonPlayer $entity */
		if(!$player->isLoaded()) return false;
		if($player->isVanished()) return false;
		if($entity instanceof Player && $entity->isVanished()) return false;
		
		if($entity instanceof Guard || ($entity instanceof EntityDamageByEntityEvent && $entity->getDamager() instanceof Guard)){
			return true;
		}
		$mines = $this->plugin->getMines();

		if($entity instanceof EntityDamageByEntityEvent) $entity = $entity->getDamager();

		$gm = Prison::getInstance()->getGangs()->getGangManager();
		if(
			$gm->inGang($player) &&
			($gang = $gm->getPlayerGang($player))->inBattle() &&
			$entity instanceof Player &&
			$gm->inGang($entity) && ($hg = $gm->getPlayerGang($entity)) !== $gang &&
			$hg->inBattle() &&
			($gb = $gang->getBattle())->getId() == $hg->getBattle()->getId()
		){
			if(
				$gb->getStatus() == Battle::GAME_START &&
				$gb->isParticipating($player) &&
				!$gb->isSpectator($player)
			){
				return true;
			}
			return false;
		}

		$session = $player->getGameSession()->getCombat();
		/** @var PrisonPlayer $entity */
		if($session->isInvincible() || ($entity instanceof Player && $entity->hasGameSession() && ($esession = $entity->getGameSession())->getCombat()->isInvincible())){
			return false;
		}

		if($player->getGameSession()->getKoth()->inGame()) return true;

		if($session->inPvPMode()){
			if(($entity instanceof Player && $esession->getCombat()->inPvPMode()) || (
				$entity instanceof EntityDamageByEntityEvent &&
				$entity->getDamager() instanceof Player &&
				$entity->getDamager()->getGameSession()->getCombat()->inPvPMode()
			)){
				return true;
			}
		}

		$session = $player->getGameSession()->getMines();
		if($session->inMine()){
			if($session->getMine()->pvp()){
				return true;
			}
		}

		return false;
	}

	public function processKill(Entity $killer, Entity $hit) : void{
		if($killer instanceof Player){
			$inv = $killer->getInventory();
			if($inv != null){
				$hand = $killer->getInventory()->getItemInHand();
				$item = $this->plugin->getEnchantments()->getItemData($hand);

				if($item->hasEffect()){
					$effect = $item->getEffect();
					$callable = $effect->getCallable();
					$callable($killer, $hit->getPosition());
				}
			}
		}

		if($hit instanceof Player && $killer instanceof Player){
			$gm = Prison::getInstance()->getGangs()->getGangManager();
			if(
				$gm->inGang($hit) && $gm->inGang($killer) &&
				($hgang = $gm->getPlayerGang($hit))->inBattle() &&
				($kgang = $gm->getPlayerGang($killer))->inBattle() &&

				($battle = $hgang->getBattle())->getId() == $kgang->getBattle()->getId() &&
				$battle->isParticipating($hit) &&
				$battle->isParticipating($killer)
			){
				$battle->addKill($kgang);

				$kp = $battle->getParticipantBy($killer);
				$kp->addKill();

				$hp = $battle->getParticipantBy($hit);
				if(!$hp->takeLive()){
					foreach($battle->getParticipants() as $pp){
						if(($pl = $pp->getPlayer()) instanceof Player)
							$pl->sendMessage(TextFormat::RI . (($hid = $hgang->getId()) == ($pid = $pp->getGang()->getId()) ? TextFormat::GREEN : TextFormat::RED) . $hit->getName() . TextFormat::GRAY . " was ELIMINATED by " . (($kid = $kgang->getId()) == $pid ? TextFormat::GREEN : TextFormat::RED) . $killer->getName());

					}
					$battle->removeParticipant($hit, true);
					if(!$battle->isCancelled()) $battle->addSpectator($hit);
				}else{
					foreach($battle->getParticipants() as $pp){
						if(($pl = $pp->getPlayer()) instanceof Player)
							$pl->sendMessage(TextFormat::RI . (($hid = $hgang->getId()) == ($pid = $pp->getGang()->getId()) ? TextFormat::GREEN : TextFormat::RED) . $hit->getName() . TextFormat::GRAY . " was killed by " . (($kid = $kgang->getId()) == $pid ? TextFormat::GREEN : TextFormat::RED) . $killer->getName() . TextFormat::GRAY . ($battle->getMode() == Battle::MODE_LIMITED_RESPAWN ? " (" . $hp->getLives() . ")" : ""));

					}
					$battle->commenceRespawn($hit);
				}
			}else{
				foreach($hit->getInventory()->getContents() as $item){
					$hit->getWorld()->dropItem($hit->getPosition(), $item);
				}
				foreach($hit->getArmorInventory()->getContents() as $item){
					$hit->getWorld()->dropItem($hit->getPosition(), $item);
				}
				foreach($hit->getCursorInventory()->getContents() as $item){
					$hit->getWorld()->dropItem($hit->getPosition(), $item);
				}

				$inv = $killer->getInventory();
				if($inv != null){
					$hand = $killer->getInventory()->getItemInHand();
					$item = $this->plugin->getEnchantments()->getItemData($hand);

					if($hand instanceof Tool){
						$item->addKill();
						$killer->getInventory()->setItemInHand($item->getItem());
					}

					Core::announceToSS(TextFormat::LIGHT_PURPLE . $hit->getName() . TextFormat::RED . " was " . ($item->hasDeathMessage() ? $item->getDeathMessage() . TextFormat::RESET . TextFormat::RED : "killed") . " by " . TextFormat::LIGHT_PURPLE . $killer->getName() . "'s " . TextFormat::DARK_PURPLE . "[" . $item->getName() . TextFormat::RESET . TextFormat::DARK_PURPLE . "]");
				}


				$hit->getInventory()->clearAll();
				$hit->getArmorInventory()->clearAll();
				$hit->getCursorInventory()->clearAll();

				$hit->getWorld()->dropExperience($hit->getPosition(), $hit->getXpDropAmount());
				$hit->getXpManager()->setCurrentTotalXp(0);

				Prison::getInstance()->getEnchantments()->calculateCache($hit);
				/** @var PrisonPlayer $hit */
				/** @var PrisonPlayer $killer */
				$hit->stopBleeding();
				$this->resetPlayer($hit);

				$killer->addTechits(5);

				$hcss = $hit->getGameSession()->getCombat();
				$kcss = $killer->getGameSession()->getCombat();
				$pvpm = false;

				if($hit->getGameSession()->getKoth()->inGame()){
					$killer->getGameSession()->getKoth()->addKill();
					$hit->getGameSession()->getKoth()->addDeath();
					$hit->getGameSession()->getKoth()->setGame();
				}


				$hit->getGameSession()->getData()->update();
				$ms = $hit->getGameSession()->getMines();
				if($ms->inMine()){
					if($ms->getMineLetter() == "pvp"){
						$kcss->addMineKill();
						$hcss->addMineDeath();
						$pvpm = true;
					}
					$ms->exitMine(false);
				}
				$hcss->untag();

				$session = $killer->getGameSession()->getQuests();
				if($session->hasActiveQuest()){
					$quest = $session->getCurrentQuest();
					switch($quest->getId()){
						case QuestIds::BILLY:
							if(!$quest->isComplete()){
								$quest->progress["kills"][0]++;
								if($quest->progress["kills"][0] >= 5){
									$quest->setComplete(true, $killer);
								}
							}
						break;
					}
				}

				if($hcss->inPvPMode()){
					$hcss->togglePvPMode();
					if(!$pvpm){
						$kcss->addPvPKill();
						$hcss->addPvPDeath();
					}
				}

				$bounties = $this->getBounties();
				if($hcss->hasBounty()){
					$killer->addTechits(($value = $hcss->getBountyValue()));
					$hcss->setBountyValue(0);
					$killer->sendMessage(TextFormat::AQUA . "Claimed " . TextFormat::YELLOW . $hit->getName() . "'s " . TextFormat::AQUA . " bounty of " . TextFormat::GREEN . number_format($value));
					if($value > 20000){
						$this->plugin->getServer()->broadcastMessage(TextFormat::AQUA . "> " . TextFormat::YELLOW . $killer->getName() . TextFormat::GRAY . " claimed a " . TextFormat::AQUA . number_format($value) . " Techit bounty" . TextFormat::GRAY . " from " . TextFormat::RED . $hit->getName() . "!");
					}
				}

				$techie = Core::getInstance()->getTechie()->getTechie();
				if($techie instanceof Techie){
					$kmessage = str_replace("{name}", $killer->getName(), self::KILL_MESSAGES[mt_rand(0, count(self::KILL_MESSAGES) - 1)]);
					$dmessage = str_replace("{name}", $hit->getName(), self::DEATH_MESSAGES[mt_rand(0, count(self::DEATH_MESSAGES) - 1)]);

					if(mt_rand(0, 5) == 1){
						$kmessage .= " Here's 20 extra Techits because of how cool that was!";
						$killer->addTechits(20);
					}

					$techie->sendMessage($killer, $kmessage);
					$techie->sendMessage($hit, $dmessage);
				}

				$kgang = $gm->getPlayerGang($killer);
				$hgang = $gm->getPlayerGang($hit);

				$gm = Prison::getInstance()->getGangs()->getGangManager();
				if($gm->inGang($killer)){
					if($kgang->addKill(1, $hit)){
						$killer->sendMessage(TextFormat::GI . "Nice kill! You earned your gang " . TextFormat::GOLD . TrophyData::EVENT_KILL . " trophies!");
					}
					$km = $kgang->getMemberManager()->getMember($killer);
					if($km != null){
						$km->addKill(1);
					}
				}
				if($gm->inGang($hit)){
					if($hgang->addDeath(1, $hit, $killer)){
						$hit->sendMessage(TextFormat::GI . "Ouch.. That death lost your gang " . TextFormat::GOLD . TrophyData::EVENT_DEATH . " trophies!");
					}
					$hm = $hgang->getMemberManager()->getMember($hit);
					if($hm != null){
						$hm->addDeath(1);
					}
				}
			}
		}elseif($hit instanceof Player && $killer instanceof Guard){
			$killer->setMode(Guard::MODE_PATH);

			$items = array_merge(
				$hit->getInventory()->getContents(),
				$hit->getArmorInventory()->getContents(),
				$hit->getCursorInventory()->getContents(),
				$hit->getCraftingGrid()->getContents()
			);
			/** @var PrisonPlayer $hit */

			$hit->getGameSession()->getGuards()->addBin(
				$bin = new Bin($hit->getUser(), $items)
			);
			$hit->sendMessage(TextFormat::BOLD . TextFormat::GOLD . "Guard: " . TextFormat::RESET . TextFormat::GRAY . $killer->getRandomDialogue("lesson"));
			$hit->sendMessage(TextFormat::RI . "Your items were confiscated by the guard! You can get them back for a fee of " . TextFormat::AQUA . number_format($bin->getPrice()) . " Techits " . TextFormat::GRAY . "from the " . TextFormat::YELLOW . "Lost and Found " . TextFormat::GRAY . "(" . TextFormat::AQUA . "/bin" . TextFormat::GRAY . ")");

			$hit->getInventory()->clearAll();
			$hit->getArmorInventory()->clearAll();
			$hit->getCursorInventory()->clearAll();
			$hit->getCraftingGrid()->clearAll();

			$hit->getWorld()->dropExperience($hit->getPosition(), $hit->getXpDropAmount());
			$hit->getXpManager()->setCurrentTotalXp(0);

			Prison::getInstance()->getEnchantments()->calculateCache($hit);
			$hit->stopBleeding();
			$this->resetPlayer($hit);

		}
	}

	public function processSuicide(Player $player){
		foreach($player->getInventory()->getContents() as $item){
			$player->getWorld()->dropItem($player->getPosition(), $item);
		}
		foreach($player->getArmorInventory()->getContents() as $item){
			$player->getWorld()->dropItem($player->getPosition(), $item);
		}
		foreach($player->getCursorInventory()->getContents() as $item){
			$player->getWorld()->dropItem($player->getPosition(), $item);
		}
		foreach($player->getOffhandInventory()->getContents() as $item){
			$player->getWorld()->dropItem($player->getPosition(), $item);
		}
		foreach($player->getCraftingGrid()->getContents() as $item){
			$player->getWorld()->dropItem($player->getPosition(), $item);
		}
		$player->getInventory()->clearAll();
		$player->getArmorInventory()->clearAll();
		$player->getOffhandInventory()->clearAll();
		$player->getCursorInventory()->clearAll();
		$player->getCraftingGrid()->clearAll();

		$player->getWorld()->dropExperience($player->getPosition(), $player->getXpDropAmount());
		$player->getXpManager()->setCurrentTotalXp(0);

		Prison::getInstance()->getEnchantments()->calculateCache($player);
		/** @var PrisonPlayer $player */
		$player->stopBleeding();

		$this->resetPlayer($player);

		if(($koth = $player->getGameSession()->getKoth())->inGame()){
			$koth->addDeath();
			$koth->setGame();
		}
		
		($gs = $player->getGameSession())->getData()->update();
		$ms = $gs->getMines();
		if($ms->inMine()){
			$ms->exitMine(true);
		}

		if($gs->getCombat()->inPvPMode()){
			$gs->getCombat()->togglePvPMode();
		}
		$gs->getCombat()->untag();
	}

	public function resetPlayer(PrisonPlayer $player) {
		$player->setMaxHealth(20);
		$player->setHealth(20);
		$player->getHungerManager()->setFood(20);
		$player->setFireTicks(0);
		$player->setBleedTicks(0);
		$player->getEffects()->clear();

		/** @var PrisonPlayer $player */
		$player->gotoSpawn();

		$player->setGamemode(GameMode::ADVENTURE());
		$player->setAllowFlight(true);
	}

	public function removeChildEntities(Player $player) : int{
		$count = 0;
		foreach($player->getServer()->getWorldManager()->getWorlds() as $level){
			foreach($level->getEntities() as $entity){
				if($entity instanceof Projectile && $entity->getOwningEntity() === $player){
					$entity->close();
					$count++;
				}
			}
		}
		return $count;
	}

	public function isCommandWhitelisted(string $command) : bool{
		return in_array($command, self::WHITELISTED_COMMANDS);
	}

}