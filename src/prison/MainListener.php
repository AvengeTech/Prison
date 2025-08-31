<?php

namespace prison;

use core\Core;
use pocketmine\event\Listener;
use pocketmine\player\GameMode;
use pocketmine\player\Player;
use pocketmine\event\player\{
	PlayerCreationEvent,
	PlayerItemUseEvent,
	PlayerJoinEvent,
	PlayerMoveEvent,
	PlayerQuitEvent,
	PlayerInteractEvent,
	PlayerJumpEvent,
	PlayerDeathEvent,
	PlayerItemHeldEvent,
	PlayerChatEvent,
	PlayerDropItemEvent,
	PlayerItemConsumeEvent,
	PlayerToggleFlightEvent,
	PlayerBucketFillEvent,
	PlayerBucketEmptyEvent
};
use pocketmine\event\block\{
	BlockPlaceEvent,
	BlockBreakEvent,
	SignChangeEvent,
	BlockUpdateEvent,
	LeavesDecayEvent
};
use pocketmine\network\mcpe\convert\TypeConverter;
use pocketmine\network\mcpe\protocol\{
	InventoryTransactionPacket,
	SetPlayerGameTypePacket
};
use pocketmine\event\entity\{
	EntityDamageEvent,
	EntityDamageByEntityEvent,
	EntityDamageByChildEntityEvent,
	EntityTeleportEvent,
	EntityShootBowEvent,

	EntityBlockChangeEvent,
	ItemSpawnEvent,
	EntityItemPickupEvent
};
use pocketmine\event\inventory\{
	CraftItemEvent,
	InventoryTransactionEvent
};
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\event\server\CommandEvent;
use pocketmine\inventory\{
	ArmorInventory,
	PlayerInventory,
};
use pocketmine\block\inventory\{
	BlockInventory,
	CraftingTableInventory
};

use pocketmine\math\Vector3;
use pocketmine\inventory\transaction\action\{
	SlotChangeAction,
};
use pocketmine\network\mcpe\protocol\{
	types\inventory\UseItemOnEntityTransactionData
};
use pocketmine\block\{
	BlockToolType,
	BlockTypeIds,
	VanillaBlocks,

	Button,
	ConcretePowder,
	Crops,
	Dirt,
	Door,
	EnchantingTable,
	Trapdoor,
	Fire,
	Grass,
};
use pocketmine\item\{
	Durable,
	Axe,
	Pickaxe,
	Shovel,
	Shears,
	Hoe,
	Food,
	Armor,
	ExperienceBottle,
	FlintSteel,
	ItemBlock,
	PaintingItem,
	Sword,
	VanillaItems
};
use pocketmine\block\tile\{
	Hopper,
	ItemFrame
};
use pocketmine\world\{
	sound\GhastShootSound,
};

use prison\settings\PrisonSettings;
use prison\gangs\objects\Gang;
use prison\gangs\battle\{
	BattleKit,
	Battle
};
use prison\guards\inventory\BinInventory;
use prison\vaults\inventory\VaultInventory;
use prison\quests\Structure as QuestIds;
use prison\enchantments\book\RedeemableBook;

use prison\trash\inventory\TrashInventory;
use prison\trade\inventory\TradeInventory;
use prison\entity\ArmorStand;
use prison\grinder\mobs\{
	Cow,
	Chicken
};
use prison\cells\Cell;
use prison\cells\ui\CellInfoUi;
use prison\cells\stores\ui\view\ViewStoresUi;
use prison\mines\PrestigeMine;
use prison\guards\entity\Guard;
use prison\enchantments\inventory\RefineEssenceInventory;
use prison\enchantments\uis\enchanter\SelectItemUi;
use prison\fishing\event\FishingCatchEvent;
use prison\fishing\object\FishingFind;
use prison\item\EssenceOfSuccess;
use prison\skills\events\SkillGainXpEvent;
use prison\skills\events\SkillLevelUpEvent;
use prison\skills\Skill;

use core\network\Links;
use core\discord\objects\{
	Post,
	Webhook
};
use core\items\type\TieredTool;
use core\utils\conversion\LegacyItemIds;
use core\utils\ItemRegistry;
use core\utils\TextFormat;
use core\vote\Vote;

use MyPlot\MyPlot;
use MyPlot\events\{
	MyPlotPlayerEnterPlotEvent,
	MyPlotPlayerLeavePlotEvent
};

class MainListener implements Listener {

	public function __construct(public Prison $plugin) {
	}

	/**
	 * @priority HIGHEST
	 */
	public function onCreation(PlayerCreationEvent $e) {
		$e->setPlayerClass(PrisonPlayer::class);
	}

	/**
	 * @priority HIGH
	 */
	public function onJoin(PlayerJoinEvent $e) {
		$player = $e->getPlayer();
		$this->plugin->onPreJoin($player);

		/*if($player->getName() == "Sn3akPeak"){
			$pk = new GameRulesChangedPacket();
			$pk->gameRules["showcoordinates"] = [1, true];
			$player->getNetworkSession()->sendDataPacket($pk);

			//($g = $this->plugin->getGuards())->spawnGuard($g->getPathManager()->getPath("test-stairs"));
		}*/
	}

	public function onMove(PlayerMoveEvent $e) {
		/** @var PrisonPlayer $player */
		$player = $e->getPlayer();

		if ($player->isCreative()) return;
		if ($player->isOnGround()) $player->toggleGlide(false);
		if ($player->getPosition()->getY() <= 0) {
			if ($player->getGameSession()->getMines()->inMine()) {
				$mine = $player->getGameSession()->getMines()->getMine();
				$mine->teleportTo($player);
			} elseif ($player->isBattleParticipant()) {
				$player->teleport($player->getGangBattle()->getArena()->getCenter()->getDot());
			} elseif ($this->plugin->getKoth()->inGame($player)) {
				$this->plugin->getKoth()->getGameByPlayer($player)->teleportTo($player);
			} else {
				$player->gotoSpawn();
			}
		}
		foreach ($this->plugin->getMysteryBoxes()->getBoxes() as $box) {
			$box->doRenderCheck($player);
		}
		foreach ($this->plugin->getLeaderboards()->getLeaderboards() as $leaderboard) {
			$leaderboard->doRenderCheck($player);
		}
		$ksession = $player->getGameSession()->getKoth();
		if ($ksession->inGame()) {
			$game = $ksession->getGame();
			if (!$game->isInBorder($player)) {
				$game->nudge($player);
				$player->sendTip(TextFormat::RED . "Please stay in the KOTH arena!");
			}
		}
		/*if($player->getName() == "Sn3akPeak"){
			$dv = $player->getDirectionVector();
			$player->sendTip(
				"DVX: " . round($dv->x) .
				" - DVY: " . round($dv->y) .
				" - DVZ: " . round($dv->z)
			);
		}*/
		if ($player->inSpawn()) {
			if ($player->getPosition()->getY() <= 18) {
				$player->gotoSpawn();
			} elseif ($player->getPosition()->getY() >= 66) {
				$player->setMotion(new Vector3(0, -0.5, 0));
			}
		} else {
			$session = $player->getGameSession()->getMines();
			if ($session->inMine()) {
				$mine = $session->getMine();
				if ($player->getGamemode() !== GameMode::CREATIVE()) {
					if ($mine->inMiningRange($player->getPosition())) {
						if ($player->getGamemode() !== GameMode::SURVIVAL()) $player->setGamemode(GameMode::SURVIVAL());
					} elseif (!$mine->pvp() && !$mine->inWalkingRange($player->getPosition())) {
						$mine->teleportTo($player);
					} else {
						if ($player->getGamemode() !== GameMode::ADVENTURE()) $player->setGamemode(GameMode::ADVENTURE());
					}
				}
				if (
					$mine->pvp() &&
					$player->getPosition()->getY() >= 80
				) {
					$player->setMotion(new Vector3(0, -1.5, 0));
				} elseif (
					$mine instanceof PrestigeMine &&
					$player->getPosition()->getY() > 70
				) {
					$player->setMotion(new Vector3(0, -1.5, 0));
				}
			}
		}
	}

	public function onQuit(PlayerQuitEvent $e) {
		$player = $e->getPlayer();
		//$this->plugin->onQuit($player);
	}

	public function onItemUse(PlayerItemUseEvent $e) {
		/** @var PrisonPlayer $player */
		$player = $e->getPlayer();
		$item = $e->getItem();

		if (
			$item->getNamedTag()->getInt(BattleKit::BATTLE_TAG, 0) == 1 &&
			!$player->isBattleParticipant()
		) {
			$e->cancel();
			$player->getInventory()->removeItem($item);
			return;
		}

		// For another season
		// if($item instanceof TieredTool || $item instanceof Armor){ // add fishing rod for fishing skill?
		// 	foreach($player->getGameSession()->getSkills()->getSkills() as $skill){
		// 		if($this->plugin->getSkills()->canUseItem($item, $skill->getLevel(), true, $skill->getIdentifier())) continue;

		// 		if($item instanceof Armor) $player->sendMessage(TextFormat::RI . 'Can not equip this item, you do not have a high enough combat skill level!');

		// 		$e->cancel();
		// 		return;
		// 	}
		// }

		if (
			$item instanceof Armor or
			$item->getTypeId() == ItemRegistry::ELYTRA()->getTypeId()
		) {
			$session = $player->getGameSession()->getMines();
			if (
				$session->inMine() && $session->getMine()->pvp() &&
				$e->getItem()->getTypeId() == ItemRegistry::ELYTRA()->getTypeId()
			) {
				$e->cancel();
				return;
			}
			$this->plugin->getEnchantments()->calculateCache($player);
		}

		if ($item instanceof RedeemableBook) {
			$e->cancel();
			$item->redeem($player);
		}
	}

	// For another season
	// public function onCatchFish(FishingCatchEvent $e) : void{
	// 	/** @var PrisonPlayer $player */
	// 	$player = $e->getPlayer();
	// 	$fishingFind = $e->getFishingFind();
	// 	$skill = $player->getSkill(Skill::SKILL_FISHING);

	// 	if(mt_rand(0, 100) < 5){
	// 		// Treasure: 100 | Fish: 50 | Junk: 15
	// 		$maxXP = 5 * ($fishingFind->getCategory() === FishingFind::CATEGORY_TREASURE ? 20 : ($fishingFind->getCategory() === FishingFind::CATEGORY_FISH ? 10 : 3));

	// 		$event = new SkillGainXpEvent($player, $skill);
	// 		$event->call();

	// 		if($event->isCancelled()) return;

	// 		$skill->addExperience(mt_rand(1, $maxXP));
	// 	}
	// }

	public function onGainSkillXp(SkillGainXpEvent $ev): void {
		$player = $ev->getPlayer();
		$skill = $ev->getSkill();

		if (!$skill->canLevelUp()) return;

		$event = new SkillLevelUpEvent($player, $skill);
		$event->call();

		if ($event->isCancelled()) return;

		$skill->levelUp($skill);
	}

	public function onSkillLevelUp(SkillLevelUpEvent $ev): void {
		$player = $ev->getPlayer();
		$skill = $ev->getSkill();

		$player->sendToastNotification(TextFormat::LIGHT_PURPLE . ucfirst($skill->getIdentifier()) . ' Skill Level Up', TextFormat::BOLD . TextFormat::GREEN . $skill->getLevel() . TextFormat::GRAY . ' -> ' . TextFormat::RED . ($skill->getLevel() + 1));
	}

	public function onInteract(PlayerInteractEvent $e) {
		/** @var PrisonPlayer $player */
		$player = $e->getPlayer();
		$block = $e->getBlock();
		$item = $e->getItem();

		if (
			$player->isBattleParticipant() &&
			$player->getGangBattle()->getStatus() == Battle::GAME_GET_READY
		) {
			$e->cancel();
			return;
		}

		// For another season
		// if($item instanceof TieredTool && ($block instanceof Dirt || $block instanceof Grass)){
		// 	$skill = $player->getSkill(Skill::SKILL_FARMING);

		// 	if(!$this->plugin->getSkills()->canUseItem($item, $skill->getLevel(), true, $skill->getIdentifier())){
		// 		$e->cancel();
		// 		return;
		// 	};

		// 	if(mt_rand(0, 100) < 3){
		// 		$event = new SkillGainXpEvent($player, $skill);
		// 		$event->call();

		// 		if($event->isCancelled()) return;

		// 		$skill->addExperience(mt_rand(1, 15));
		// 	}
		// }

		if ($player->isTier3() && $e->getAction() == 1) {
			$ss = $player->getGameSession()->getGuards();
			if ($ss->inPathMode()) {
				$e->cancel();
				if ($ss->canTap()) {
					$ss->addPoint(($p = $block->getPosition()->asVector3()->add(0, 1, 0)));
					$player->sendMessage(TextFormat::GI . "Successfully set path point at " . TextFormat::YELLOW . $p);
				}
				return;
			}
		}

		if ($player->isSn3ak() && $e->getAction() == PlayerInteractEvent::RIGHT_CLICK_BLOCK) {
			$player->sendTip("Block: " . $block->getName() . " - ID: " . $block->getTypeId() . " - StateId: " . $block->getStateId() . PHP_EOL . "Pos: X:" . $block->getPosition()->getX() . "/Y:" . $block->getPosition()->getY() . "/Z:" . $block->getPosition()->getZ());
		}

		if ($player->inSpawn()) {
			if ($item instanceof PaintingItem) {
				$e->cancel();
				return;
			}
			$mb = $this->plugin->getMysteryBoxes();
			$box = $mb->getBoxByPos($block->getPosition()->asVector3());
			if ($box != null) {
				$e->cancel();
				if ($mb->isOpeningBox($player)) {
					$player->sendMessage(TextFormat::RED . "Please only open one box at a time!");
					return;
				}
				$session = $player->getGameSession()->getMysteryBoxes();
				$keys = $session->getKeys($box->getTier());
				if ($keys <= 0) {
					$text = TextFormat::RED . TextFormat::BOLD . "(!) " . TextFormat::RESET . TextFormat::GRAY . "You don't have any " . $box->getTier() . " keys!";
					if ($box->getTier() == "vote") {
						$text .= " Get them for free by voting! Learn how to vote by typing " . TextFormat::YELLOW . "/vote" . TextFormat::GRAY . " in the chat!";
					} elseif ($box->getTier() == "divine") {
						$text .= " Get 2 for free every time you prestige!";
					} else {
						$text .= " Find them by mining, purchase them with Quest Points, or buy them at " . TextFormat::YELLOW . Links::SHOP;
					}
					$player->sendMessage($text);
					return;
				}
				if (!$player->getInventory()->canAddItem(VanillaBlocks::RESERVED6()->asItem())) {
					$player->sendMessage(TextFormat::RED . "Your inventory is full! Please empty your inventory before using this.");
					return;
				}
				$box->sendUi($player);
				return;
			}

			if ($block instanceof Button) {
				$cm = $this->plugin->getCells()->getCellManager();
				$cell = $cm->getCellByButton($block);
				if ($cell instanceof Cell) {
					if ($cell->getStoreButton() === $block) {
						if (empty(($hm = $cell->getHolderManager())->getHolders())) {
							$player->sendMessage(TextFormat::RI . "This cell is not claimed!");
						} else {
							$owner = $hm->getOwner();
							if ($owner->getXuid() == $player->getXuid()) {
								$player->sendMessage(TextFormat::RI . "You cannot purchase from your own store!");
							} elseif (empty($owner->getStoreManager()->getStores(true))) {
								$player->sendMessage(TextFormat::RI . "This cell holder has no cell stores open!");
							} else {
								$player->showModal(new ViewStoresUi($player, $cell, $owner));
							}
						}
					} elseif ($cell->getQueueButton() === $block) {
						$player->showModal(new CellInfoUi($player, $cell));
					}
					$e->cancel();
				}
			}
		}

		if ($player->inLegacyPlotWorld() && !$player->isTier3()) {
			$e->cancel();
		}
		$tile = $player->getWorld()->getTile($block->getPosition());

		if ($tile != null) {
			$session = $player->getGameSession()->getVaults();
			if ($session->inVault()) $e->cancel();
			if ($tile instanceof Hopper) $e->cancel();
		}

		$plots = MyPlot::getInstance();
		if ($player->inPlotWorld()) {
			$face = $e->getFace();
			if ($tile != null || $block instanceof Door || $block instanceof Trapdoor || ($block->getSide($face) instanceof Fire && $e->getAction() == PlayerInteractEvent::LEFT_CLICK_BLOCK)) {
				$plot = $plots->getPlotByPosition($e->getBlock()->getPosition());
				if ($plot === null) {
					$e->cancel();
					return;
				}
				if (
					($rel = $plot->owner != ($username = (int)$player->getXuid()) &&
						!$plot->isHelper($username)) &&
					!$player->isStaff()
				) {
					$e->cancel();
					return;
				}
				if ($rel && $tile instanceof ItemFrame) {
					$e->cancel();
					return;
				}
			} elseif (!$e->getItem() instanceof Food) {
				$plots->eventListener->onEventOnBlock($e);
			}
		} else {
			$face = $e->getFace();
			if (($block->getSide($face) instanceof Fire && $e->getAction() == PlayerInteractEvent::LEFT_CLICK_BLOCK)) {
				$e->cancel();
				return;
			}
			if ($item instanceof Shovel || $item instanceof Hoe || $item instanceof FlintSteel || $item instanceof Axe) {
				if (!$player->isTier3()) $e->cancel();
			}
		}

		if ($block instanceof EnchantingTable) {
			$e->cancel();
			$player->showModal(new SelectItemUi($player));
		}
	}

	public function onJump(PlayerJumpEvent $e) {
		/** @var PrisonPlayer $player */
		$player = $e->getPlayer();
		if (!$player->isLoaded()) {
			return;
		}
		$session = $player->getGameSession()->getQuests();
		if ($session->hasActiveQuest()) {
			$quest = $session->getCurrentQuest();
			switch ($quest->getId()) {
				case QuestIds::GARY_GRASSHOPPER:
					if (!$quest->isComplete()) {
						$quest->progress["jumps"][0]++;
						if ($quest->progress["jumps"][0] >= 250) {
							$quest->setComplete(true, $player);
						}
					}
					break;
			}
		}
	}

	public function onDeath(PlayerDeathEvent $e) {
		/** @var PrisonPlayer $player */
		$player = $e->getPlayer();
		$combat = $this->plugin->getCombat();

		$e->setDrops([]);

		$cause = $player->getLastDamageCause();
		if ($cause instanceof EntityDamageByEntityEvent) {
			$combat->processKill($cause->getDamager(), $player);
		} else {
			if (($session = $player->getGameSession()->getCombat())->isTagged() && ($tagger = $session->getTagger()) instanceof Player) {
				$combat->processKill($tagger, $player);
			} else {
				$combat->processSuicide($player);
			}
		}
	}

	public function onCmd(CommandEvent $e) {
		/** @var PrisonPlayer $player */
		$player = $e->getSender();
		if (!($player instanceof Player)) return;
		if (!$player->isLoaded()) {
			$e->cancel();
			return;
		}
		$msg = "/" . $e->getCommand();
		if ($msg[0] == "/" || $msg[0] == ".") {
			$msg = ltrim(ltrim($msg, "."), "/");
			if ($player->getGameSession()->getCombat()->isTagged() && !$this->plugin->getCombat()->isCommandWhitelisted($msg)) {
				$e->cancel();
				$player->sendMessage(TextFormat::RED . "You cannot run commands in combat mode!");
				return;
			}

			$gm = $this->plugin->getGangs()->getGangManager();
			if ($gm->inGang($player) && ($gang = $gm->getPlayerGang($player))->inBattle() && ($ba = $gang->getBattle())->isParticipating($player) && $ba->getStatus() > 1) {
				$e->cancel();
				$player->sendMessage(TextFormat::RED . "You cannot run commands while participating in a battle!");
				return;
			}
		}
	}

	public function onToggleFlight(PlayerToggleFlightEvent $e) {
		/** @var PrisonPlayer $player */
		$player = $e->getPlayer();
		$flying = $e->isFlying();
		if ($flying && !$player->inFlightMode()) {
			$e->cancel();
			$player->doubleJump();
		}
	}

	/**
	 * @priority HIGHEST
	 */
	public function onBucketFill(PlayerBucketFillEvent $e) {
		$e->cancel();
	}

	/**
	 * @priority HIGHEST
	 */
	public function onBucketEmpty(PlayerBucketEmptyEvent $e) {
		$e->cancel();
	}

	public function onPlace(BlockPlaceEvent $e) {
		/** @var PrisonPlayer $player */
		$player = $e->getPlayer();

		if ($player->isCreative()) return;

		if ($player->getGameSession()->getMines()->inMine()) {
			if (!$player->isTier3() || !Prison::getInstance()->isTestServer()) {
				$e->cancel();
			}
		} else {
			$plots = MyPlot::getInstance();
			if (!$player->inPlotWorld() && !$player->isTier3()) {
				$e->cancel();
				return;
			}
			$plots->eventListener->onEventOnBlock($e);
		}
	}

	public function onHeld(PlayerItemHeldEvent $e) {
	}

	/**
	 * @priority LOWEST
	 */
	public function onChat(PlayerChatEvent $e) {
		$message = $e->getMessage();
		/** @var PrisonPlayer $player */
		$player = $e->getPlayer();
		$gangs = $this->plugin->getGangs()->getGangManager();
		if ($gangs->inGang($player)) {
			$gang = $gangs->getPlayerGang($player);
			switch ($gang->getMemberManager()->getMember($player)->getChatMode()) {
				default:
				case 0:

					break;
				case 1:
					if ($player->isMuted()) return;

					$e->cancel();
					$gang->sendMessage($player, $message);

					$post = new Post(
						"[" . $gang->getName() . "] " . $player->getName() . ": " . $message,
						"Gang Chat | " . $player->getName() . " | " . $gang->getName(),
						"[REDACTED]",
					);
					$post->setWebhook(Webhook::getWebhookByName("gang-chat-log"));
					$post->send();
					break;
				case 2:
					if ($player->isMuted()) return;

					$e->cancel();
					$gang->sendMessage($player, $message, 2);
					foreach ($gangs->getAllianceManager()->getAlliances($gang->getId()) as $ally) {
						$a = $ally->getAlly();
						if ($a instanceof Gang) {
							$a->sendMessage($player, $message, 2);
						}
					}

					$post = new Post(
						"[" . $gang->getName() . "] " . $player->getName() . ": " . $message,
						"Ally Chat | " . $player->getName() . " | " . $gang->getName(),
						"[REDACTED]",
					);
					$post->setWebhook(Webhook::getWebhookByName("gang-chat-log"));
					$post->send();
					break;
			}
		}
	}

	public function onDrop(PlayerDropItemEvent $e) {
		/** @var PrisonPlayer $player */
		$player = $e->getPlayer();
		$gm = $this->plugin->getGangs()->getGangManager();
		if ($player->isBattleSpectator() || $player->isBattleParticipant()) {
			$e->cancel();
			return;
		}
		if ($player->inLegacyPlotWorld() && !$player->isSn3ak()) {
			$e->cancel();
			return;
		}
		$item = $e->getItem();
		if (
			$player->isLoaded() &&
			$player->getGameSession()->getSettings()->getSetting(PrisonSettings::NO_TOOL_DROP) &&
			$item instanceof Durable
		) {
			if (
				$player->getInventory()->canAddItem($item) ||
				$player->getInventory()->contains($item)
			) {
				$e->cancel();
			}
			$player->sendTip(TextFormat::EMOJI_DENIED . TextFormat::RED . " You disabled tool dropping!");
			return;
		}
	}

	public function onConsume(PlayerItemConsumeEvent $e) {
		/** @var PrisonPlayer $player */
		$player = $e->getPlayer();
		$item = $e->getItem();
		if (
			$item->getNamedTag()->getInt(BattleKit::BATTLE_TAG, 0) == 1 &&
			!$player->isBattleParticipant()
		) {
			$e->cancel();
			$player->getInventory()->removeItem($item);
			return;
		}
	}

	public function onPickup(EntityItemPickupEvent $e) {
		/** @var PlayerInventory $inventory */
		$inventory = $e->getInventory();
		if ($inventory === null) return;
		/** @var PrisonPlayer $player */
		$player = $inventory->getHolder();
		$gm = $this->plugin->getGangs()->getGangManager();
		if ($player instanceof Player) {
			if (
				$player->getGameSession()->getCombat()->isInvincible() ||
				$player->isBattleSpectator() || $player->isBattleParticipant() ||
				$player->inLegacyPlotWorld() || $player->isVanished() && Core::getInstance()->getVote()->getPartyStatus() !== Vote::STATUS_START
			) {
				$e->cancel();
			}
		}
	}

	public function onBreak(BlockBreakEvent $e) {
		$e->setXpDropAmount(0);

		/** @var PrisonPlayer $player */
		$player = $e->getPlayer();

		if (Core::thisServer()->isTestServer() && $player->isCreative()) return;

		$block = $e->getBlock();
		$mines = $this->plugin->getMines();
		$item = $e->getItem();

		// if($item instanceof TieredTool){
		// 	if($block instanceof Crops){
		// 		$skill = $player->getSkill(Skill::SKILL_FARMING);
		// 	}else{
		// 		$skill = $player->getSkill(Skill::SKILL_MINING);
		// 	}

		// 	if(!$this->plugin->getSkills()->canUseItem($item, $skill->getLevel(), true, $skill->getIdentifier())){
		// 		$e->cancel();
		// 		return;
		// 	}

		// 	if(mt_rand(0, 100) < 3){
		// 		$event = new SkillGainXpEvent($player, $skill);
		// 		$event->call();

		// 		if($event->isCancelled()) return;

		// 		$skill->addExperience(mt_rand(1, 15));
		// 	}
		// }

		$ench = $this->plugin->getEnchantments();

		$session = ($gs = $player->getGameSession())->getMines();

		if ($session->inMine()) {
			$mine = $session->getMine();
			if ($mine->inMine($block->getPosition())) {
				if ($gs->getCombat()->isInvincible($player)) {
					$player->sendMessage(TextFormat::RN . "You cannot mine while invincible!");
					$e->cancel();
				} elseif ($mine->isResetting()) {
					$player->sendMessage(TextFormat::RN . "This mine is currently resetting! Please wait");
					$e->cancel();
				} elseif ($player->isVanished() && !$player->isTier3()) {
					$player->sendMessage(TextFormat::RN . "You cannot mine while vanished! (Lead Mod only)");
					$e->cancel();
				} else {
					if ($block->getTypeId() == VanillaBlocks::IRON_ORE()->getTypeId()) $e->setDrops([VanillaItems::IRON_INGOT()]);
					if ($block->getTypeId() == VanillaBlocks::GOLD_ORE()->getTypeId()) $e->setDrops([VanillaItems::GOLD_INGOT()]);

					$session->addStreak();
					$mine->addTotalMined();

					$data = $ench->getItemData($item);
					if (TieredTool::isAxe($item) || TieredTool::isPickaxe($item) || TieredTool::isShovel($item) || ($item instanceof TieredTool && $item->getBlockToolType() == BlockToolType::SHEARS)) {
						/** @var TieredTool $item */
						$data->addBlocksMined();

						$returnedItems = []; // Throws an error if I don't do it like this 

						$data->getItem()->onDestroyBlock($block, $returnedItems);
						$player->getInventory()->setItemInHand($data->getItem());
						if (
							($hitsLeft = $item->getMaxDurability() - $item->getDamage()) <= 32 &&
							$player->getGameSession()->getSettings()->getSetting(PrisonSettings::TOOL_BREAK_ALERT)
						) {
							$player->sendTip(TextFormat::EMOJI_CAUTION . TextFormat::YELLOW . " Tool has " . TextFormat::RED . $hitsLeft . TextFormat::YELLOW . " durability left!");
						}
					}
					$ench->process($e);

					$drops = $e->getDrops();
					foreach ($drops as $key => $drop) {
						if ($drop->equals(VanillaBlocks::IRON_ORE()->asItem(), false, false) || $drop->equals(VanillaItems::RAW_IRON(), false, false)) $drops[$key] = VanillaItems::IRON_INGOT()->setCount($drop->getCount());
						if ($drop->equals(VanillaBlocks::GOLD_ORE()->asItem(), false, false) || $drop->equals(VanillaItems::RAW_GOLD(), false, false)) $drops[$key] = VanillaItems::GOLD_INGOT()->setCount($drop->getCount());
					}
					$e->setDrops($drops);

					$drops = $e->getDrops();
					if (!($player->getGameSession()->getSettings()->getSetting(PrisonSettings::AUTOSELL) || $player->getGameSession()->getShops()->isActive()) || ($player->getRankHierarchy() < 5 && !$player->getGameSession()->getShops()->isActive())) {
						foreach ($drops as $drop) {
							if (!$player->getInventory()->canAddItem($drop)) {
								$player->sendTitle(TextFormat::DARK_RED . TextFormat::BOLD . "Hey!", TextFormat::RED . "Your inventory is full!", 5, 10, 10);
								$player->playSound("random.anvil_land");
							} else {
								$player->getInventory()->addItem($drop);
							}
						}
					} else {
						$leftover = $this->plugin->getShops()->sellDrops($player, $drops, true);
						foreach ($leftover as $drop) {
							if (!$player->getInventory()->canAddItem($drop)) {
								$player->sendTitle(TextFormat::DARK_RED . TextFormat::BOLD . "Hey!", TextFormat::RED . "Your inventory is full!", 5, 10, 10);
								$player->playSound("random.anvil_land");
							} else {
								$player->getInventory()->addItem($drop);
							}
						}
					}
					$e->setDrops([]);

					$session->addMinedBlock($mine->getName());

					$bt = $this->plugin->getBlockTournament();
					$game = $bt->getGameManager()->getPlayerGame($player);
					if ($game !== null && $game->isStarted()) {
						$score = ($ss = $game->getScore($player))->addBlockMined($mine->getName());
					}

					$nokey = [BlockTypeIds::STONE, BlockTypeIds::DIRT, BlockTypeIds::SAND, BlockTypeIds::GOLD_ORE, BlockTypeIds::IRON_ORE, BlockTypeIds::OAK_LOG];
					if (!isset($nokey[$block->getTypeId()])) {
						$session = $gs->getMysteryBoxes();
						if (mt_rand(0, 200) == 1) $session->addKeysWithPopup("iron", 1, "mob.chicken.hurt");
						if (mt_rand(0, 400) == 1) $session->addKeysWithPopup("gold", 1, "mob.chicken.hurt");
						if (mt_rand(0, 750) == 1) $session->addKeysWithPopup("diamond", 1, "mob.chicken.hurt");
						if (mt_rand(0, 2000) == 1) $session->addKeysWithPopup("emerald", 1, "mob.chicken.hurt");
					}

					$gm = $this->plugin->getGangs()->getGangManager();
					if ($gm->inGang($player)) {
						$gang = $gm->getPlayerGang($player);
						if ($gang->addBlock()) {
							$player->sendMessage(TextFormat::YI . "Woohoo! You earned your gang a trophy for mining!");
						}
						$m = $gang->getMemberManager()->getMember($player);
						if ($m != null) {
							$m->addBlock();
						}
					}
				}
			} else {
				if (!$player->isTier3() || !Prison::getInstance()->isTestServer()) {
					$e->cancel();
				}
			}
		} else {
			$plots = MyPlot::getInstance();

			if (!$player->isTier3() && !$player->inPlotWorld()) {
				$e->cancel();
				return;
			}
			$block = $e->getBlock();

			if ($block->getTypeId() == BlockTypeIds::TALL_GRASS) $e->setDrops([]);

			$plots->eventListener->onEventOnBlock($e);
			if (!$e->isCancelled()) {
				$data = $ench->getItemData($item);
				if ($item instanceof Axe || $item instanceof Pickaxe || $item instanceof Shovel || $item instanceof Shears) {
					$data->addBlocksMined();
					$returnedItems = [];
					$data->getItem()->onDestroyBlock($block, $returnedItems);
					$player->getInventory()->setItemInHand($data->getItem());
					if (
						($hitsLeft = $item->getMaxDurability() - $item->getDamage()) <= 32 &&
						$player->getGameSession()->getSettings()->getSetting(PrisonSettings::TOOL_BREAK_ALERT)
					) {
						$player->sendTip(TextFormat::EMOJI_CAUTION . TextFormat::YELLOW . " Tool has " . TextFormat::RED . $hitsLeft . TextFormat::YELLOW . " durability left!");
					}
				}
				$ench->process($e);
			}
		}
	}

	public function onSignChange(SignChangeEvent $e): void {
		$plots = MyPlot::getInstance();
		/** @var PrisonPlayer $player */
		$player = $e->getPlayer();

		if (!$player->inPlotWorld()) {
			if (!$player->isTier3()) {
				$e->cancel();
				return;
			}
			return;
		}

		$plots->eventListener->onEventOnBlock($e);
	}

	/**
	 * @priority HIGHEST
	 */
	public function onUpdate(BlockUpdateEvent $e) {
		if ($e->getBlock()->getPosition()->getWorld()->getDisplayName() == "pvpmine") $e->cancel();
	}

	public function onLeavesDecay(LeavesDecayEvent $e) {
		$e->cancel();
	}

	public function onEnDmg(EntityDamageEvent $e) {
		if ($e->isCancelled()) return;

		/** @var PrisonPlayer $player */
		$player = $e->getEntity();
		$combat = $this->plugin->getCombat();
		if ($e->getCause() == EntityDamageEvent::CAUSE_FALL) {
			$e->cancel();
			return;
		}
		if ($player instanceof Player) {
			if (!$player->isLoaded()) {
				$e->cancel();
				return;
			}
			if ($e instanceof EntityDamageByEntityEvent) {
				/** @var PrisonPlayer $killer */
				if (($killer = $e->getDamager()) instanceof PrisonPlayer && $killer->getWorld()->getDisplayName() == "newpsn" && $killer->isLoaded() && !$killer->getGameSession()->getCombat()->inPvPMode()) {
					if ($killer->isStaff()) {
						$ds = $killer->getSession()->getStaff();
						if ($ds->canPunchBack($player)) {
							$ds->punchBack($player);
						} elseif ($player->isStaff()) {
							$player->getSession()?->getStaff()->punch($killer);
						}
					} else {
						$player->getSession()?->getStaff()->punch($killer);
					}
				}
			}
			if (!$combat->canCombat($player, $e)) {
				$e->cancel();
			}
		}
		if ($e instanceof EntityDamageByEntityEvent) {
			/** @var PrisonPlayer $killer */
			$killer = $e->getDamager();
			if ($killer instanceof Player) {
				$item = $killer->getInventory()->getItemInHand();

				// For another season
				// if($item instanceof TieredTool){
				// 	foreach([Skill::SKILL_COMBAT, Skill::SKILL_AXE_COMBAT] as $type){
				// 		$skill = $killer->getSkill($type);

				// 		if($this->plugin->getSkills()->canUseItem($item, $skill->getLevel(), true, $skill->getIdentifier())) continue;

				// 		$e->cancel();
				// 		return;
				// 	}
				// }

				if (!$combat->canCombat($killer, $player) && $player instanceof Player) {
					$e->cancel();
					return;
				}
				if ($killer === $player) {
					$e->cancel();
					return;
				}

				$killer->addCombo();
				if ($player instanceof Player) $player->resetCombo();

				$ench = $this->plugin->getEnchantments();
				if (!$e instanceof EntityDamageByChildEntityEvent) {
					$ench->process($e);
				}

				if ($player instanceof Player) $psession = $player->getGameSession()->getCombat();
				$ksession = $killer->getGameSession()->getCombat();
				if ($e->getFinalDamage() < $player->getHealth()) {
					if (
						$player instanceof Player && (
							$player->getGameSession()->getMines()->getMineLetter() == "pvp" ||
							$psession->inPvPMode() ||
							$player->getGameSession()->getKoth()->inGame()
						)
					) {
						if (!$psession->isTagged()) {
							$player->sendMessage(TextFormat::RED . "You are now in combat mode! You cannot run commands.");
						}
						$psession->tag($killer);
					}
				}
				if ($player instanceof Player) {
					if (!$ksession->isTagged()) {
						$killer->sendMessage(TextFormat::RED . "You are now in combat mode! You cannot run commands.");
					}
					$ksession->tag($player);
				}
			} elseif ($killer instanceof Guard) {
				$e->uncancel();
			}
			if ($e->getFinalDamage() >= $player->getHealth()) {
				if ($player instanceof Player) $e->cancel();
				$combat->processKill($killer, $player);
				return;
			}
		} else {
			if ($e->getFinalDamage() >= $player->getHealth()) {
				if ($player instanceof Player) {
					$session = $player->getGameSession()->getCombat();
					$e->cancel();
					if ($session->isTagged()) {
						$tagger = $session->getTagger();
						if ($tagger instanceof PrisonPlayer) {
							$combat->processKill($tagger, $player);

							$item = $tagger->getInventory()->getItemInHand();

							// For another season
							// if($item instanceof Axe){
							// 	$skill = $tagger->getSkill(Skill::SKILL_AXE_COMBAT);
							// }else{
							// 	$skill = $tagger->getSkill(Skill::SKILL_COMBAT);
							// }

							// if(mt_rand(0, 100) < 3){
							// 	$event = new SkillGainXpEvent($player, $skill);
							// 	$event->call();

							// 	if($event->isCancelled()) return;

							// 	$skill->addExperience(mt_rand(1, 50));
							// }
						} else {
							$combat->processSuicide($player);
						}
					} else {
						$combat->processSuicide($player);
					}
					return;
				}
			}
		}
	}

	public function onChange(EntityTeleportEvent $e) {
		/** @var PrisonPlayer $player */
		$player = $e->getEntity();
		$t = $e->getTo()->getWorld()->getDisplayName();
		$f = $e->getFrom()->getWorld()->getDisplayName();
		if ($t === $f) return;
		if ($player instanceof Player) {
			if ($t != $player->getWorld()->getdisplayName()) $this->plugin->getMysteryBoxes()->onLevelChange($player);


			if (
				!in_array($t, ["plots", "end_plots", "nether_plots"]) &&
				$player->inFlightMode()
			) {
				$player->setFlightMode(false);
			}

			$this->plugin->getLeaderboards()->changeLevel($player, $t);
		}
	}

	public function onShoot(EntityShootBowEvent $e) {
		$player = $e->getEntity();
		if ($player instanceof Player && !$e->isCancelled()) {
			$ench = $this->plugin->getEnchantments();
			$ench->process($e);
		}
	}

	public function onPlotEnter(MyPlotPlayerEnterPlotEvent $e) {
		$plots = MyPlot::getInstance();
		$player = $e->getPlayer();
		if (
			$player->inPlotWorld() &&
			($plot = $plots->getPlotByPosition($player->getLocation())) !== null &&
			(
				$plot->owner === ($xuid = (int) $player->getXuid()) ||
				$plot->isHelper($xuid)
			)
		) {
			if ($player->isAdventure(true)) $player->setGamemode(GameMode::SURVIVAL);
		} elseif ($player->isSurvival(true)) $player->setGamemode(GameMode::ADVENTURE);
	}

	public function onPlotLeave(MyPlotPlayerLeavePlotEvent $e) {
		$player = $e->getPlayer();
		if ($player->isSurvival(true)) $player->setGamemode(GameMode::ADVENTURE);
	}

	public function onBC(EntityBlockChangeEvent $e) {
		/* Whatever this did, you'll need to find a different way to proc it cause meta doesn't exist anymore

		$entity = $e->getEntity();
		if($entity instanceof FallingBlock){
			$block = $e->getTo();
			if($block->getMeta() == 9){
				$e->cancel();
				$entity->getWorld()->addSound($entity->getPosition(), new AnvilFallSound());
			}
		}
		*/
	}

	public function onItemEntSpawn(ItemSpawnEvent $e): void {
		$entity = $e->getEntity();
		$item = $entity->getItem();
		if ($item->getNamedTag()->getInt("pickup", 1) == 0) return;
		switch ($item->getTypeId()) {
			case VanillaBlocks::DIRT()->asItem()->getTypeId():
			case VanillaBlocks::NETHERRACK()->asItem()->getTypeId():
				$entity->setDespawnDelay(20 * 10);
				break;
			default:
				$entity->setDespawnDelay(20 * 30);
				break;
		}
		$entity->setPickupDelay(35);
	}

	public function onCraft(CraftItemEvent $e) {
		$player = $e->getPlayer();

		$blockedIds = [
			VanillaItems::GOLDEN_APPLE()->getTypeId(),
			VanillaItems::ENCHANTED_GOLDEN_APPLE()->getTypeId(),
			ItemRegistry::ARMOR_STAND()->getTypeId(),
			VanillaBlocks::BEACON()->asItem()->getTypeId()
		];

		foreach ($e->getOutputs() as $output) {
			if (in_array($output->getTypeId(), $blockedIds) || $output instanceof ItemBlock && $output->getBlock() instanceof ConcretePowder) {
				$e->cancel();
				$player->sendMessage(TextFormat::RED . "Crafting '" . $output->getName() . "' has been disabled.");
				break;
			}
		}
	}

	public function onTransaction(InventoryTransactionEvent $e) {
		$t = $e->getTransaction();
		/** @var PrisonPlayer $player */
		$player = $t->getSource();
		foreach ($t->getInventories() as $inventory) {
			if ($inventory instanceof RefineEssenceInventory) { // has to go before the check of if Block Inventory
				foreach ($t->getActions() as $action) {
					if (!($action instanceof SlotChangeAction)) continue;

					if ($action->getInventory() instanceof RefineEssenceInventory) {
						if ( // Basically to update the messages on the iron bars
							$action->getSlot() === $inventory->getEssenceSlot() &&
							$action->getTargetItem() instanceof EssenceOfSuccess
						) {
							$inventory->setup();
							$inventory->setItem($action->getSlot(), $action->getTargetItem());
							continue;
						}
						if ($action->getSlot() === $inventory->getBackSlot()) {
							$inventory->onBack($player);
							$e->cancel();
							continue;
						}
						if ($action->getSlot() === $inventory->getContinueSlot()) {
							$inventory->onContinue($player);
							$e->cancel();
							continue;
						}
						if (in_array($action->getSlot(), $inventory->getNoTouchSlots())) {
							$e->cancel();
							continue;
						}
					}
				}
			}
			if ($inventory instanceof BlockInventory && !$inventory instanceof CraftingTableInventory) {
				/** @var MyPlot */
				$plots = MyPlot::getInstance();

				$plot = $plots->getPlotByPosition($player->getPosition());
				if ($plot === null) {
					$e->cancel();
					return;
				}
				if (
					$plot->owner != (int)$player->getXuid() &&
					!$plot->isHelper((int)$player->getXuid()) &&
					$player->isStaff() &&
					!$player->isTier3()
				) {
					$e->cancel();
					return;
				}
			}
			if ($inventory instanceof ArmorInventory) {
				foreach ($t->getActions() as $action) {
					// For another season
					// $skill = $player->getSkill(Skill::SKILL_COMBAT);
					$session = $player->getGameSession()->getMines();

					if ($action instanceof SlotChangeAction) {
						// if($action->getInventory() instanceof PlayerInventory && !($this->plugin->getSkills()->canUseItem($action->getSourceItem(), $skill->getLevel(), $skill->getIdentifier())) && !$player->getGamemode()->equals(GameMode::CREATIVE())){
						// 	$player->sendMessage(TextFormat::RI . 'Can not equip this item, you do not have a high enough combat skill level!');

						// 	$e->cancel();
						// }elseif

						if ($session->inMine() && $session->getMine()->pvp()) {
							if ($action->getSourceItem()->getTypeId() == ItemRegistry::ELYTRA()->getTypeId()) {
								$e->cancel();
							}
						}
					}
				}
				$this->plugin->getEnchantments()->calculateCache($player);
				break;
			}
			if ($inventory instanceof VaultInventory) {
				//$e->cancel();
				//$player->sendMessage(TextFormat::RI . "Vaults are temporarily disabled while we investigate a rollback issue. To take items from your vault for now, type " . TextFormat::YELLOW . "/v <id> takeall");
				$vault = $inventory->getVault();
				$vaultplayer = $vault->getComponent()->getPlayer();
				if ($player !== $vaultplayer) {
					if ($player->getRank() != "owner" && !$player->isTier3()) {
						$e->cancel();
					}
				}
				/**foreach($t->getActions() as $action){
					if(
						$action instanceof SlotChangeAction &&
						($item = $action->getSourceItem()) instanceof ItemBlock &&
						$item->getBlock() instanceof ShulkerBox
					){
						$e->cancel();
						$player->sendMessage(TextFormat::RI . "Shulker boxes can no longer be put in vaults. To take them out of your vault, use " . TextFormat::YELLOW . "/vault <id> takeall");
					}
				}*/
				break;
			}
			if ($inventory instanceof TrashInventory) {
				foreach ($t->getActions() as $action) {
					if (stristr($action->getTargetItem()->getCustomName(), "clearing in")) {
						$e->cancel();
					}
				}
				break;
			}
			if ($inventory instanceof BinInventory) {
				$bin = $inventory->getBin();
				if (!$bin->isPaid()) {
					$e->cancel();
					return;
				}
				foreach ($t->getActions() as $action) {
					if (
						$action instanceof SlotChangeAction &&
						$action->getInventory() instanceof BinInventory
					) {
						if ($action->getSourceItem()->isNull()) $e->cancel();
					}
				}
				break;
			}
			if ($inventory instanceof TradeInventory) {
				$session = $player->getGameSession()->getTrade();
				if (!$session->isTrading()) {
					$e->cancel();
					return;
				}
				$tradesession = $session->getTradeSession();
				if ($inventory->getTradeSession() != $tradesession) {
					$e->cancel();
					return;
				}
				foreach ($t->getActions() as $action) {
					if (!($action instanceof SlotChangeAction)) continue;

					if ($action->getInventory() instanceof TradeInventory) {
						if (in_array($action->getSlot(), $inventory->getNoTouchSlots())) {
							$e->cancel();
							continue;
						}
						if ($tradesession->getPlayer1() == $player) {
							if ($action->getSlot() == $inventory->getPlayer2ButtonSlot()) {
								$e->cancel();
								return;
							}
							if ($action->getSlot() == $inventory->getPlayer1ButtonSlot()) {
								$e->cancel();
								$inventory->toggle1();
								return;
							}
							if (!in_array($action->getSlot(), $inventory->getPlayer1ItemSlots()) || $inventory->is1Toggled()) {
								$e->cancel();
								return;
							}
						} elseif ($tradesession->getPlayer2() == $player) {
							if ($action->getSlot() == $inventory->getPlayer1ButtonSlot()) {
								$e->cancel();
								return;
							}
							if ($action->getSlot() == $inventory->getPlayer2ButtonSlot()) {
								$e->cancel();
								$inventory->toggle2();
								return;
							}
							if (!in_array($action->getSlot(), $inventory->getPlayer2ItemSlots()) || $inventory->is2Toggled()) {
								$e->cancel();
								return;
							}
						}
					}
				}
			}
		}
	}

	public function onDp(DataPacketReceiveEvent $e) {
		$packet = $e->getPacket();
		/** @var PrisonPlayer $player */
		$player = $e->getOrigin()->getPlayer();

		/**if($packet instanceof PlayerActionPacket){
			if($packet->action === PlayerAction::START_GLIDE){
				$chest = $player->getArmorInventory()->getItem(1);
				if($chest->getId() !== 444){
					$e->cancel();
					$player->getNetworkSession()->getInvManager()->syncContents($player->getArmorInventory());
					return;
				}
				$player->gliding = true;
				$player->networkPropertiesDirty = true;
				return;
			}
			if($packet->action === PlayerAction::STOP_GLIDE){
				$player->gliding = false;
				$player->networkPropertiesDirty = true;
			}
			return;
		}*/

		if ($packet instanceof InventoryTransactionPacket) {
			$data = $packet->trData;
			$trType = $data->getTypeId();
			if ($trType == InventoryTransactionPacket::TYPE_USE_ITEM_ON_ENTITY) {
				/** @var UseItemOnEntityTransactionData $data */
				$type = $data->getActionType();
				switch ($type) {
					case UseItemOnEntityTransactionData::ACTION_INTERACT:
						$eid = $data->getActorRuntimeId();
						$clickPos = $data->getClickPosition();
						$slot = $data->getHotbarSlot();

						$entity = $player->getWorld()->getEntity($eid);

						if ($entity instanceof ArmorStand) {
							/** @var MyPlot $myplot */
							$myplot = $this->plugin->getServer()->getPluginManager()->getPlugin("MyPlot");
							$plot = $myplot->getPlotByPosition($entity->getPosition());

							if ($plot !== null) {
								$username = (int)$player->getXuid();
								if (
									$plot->owner == $username ||
									$plot->isHelper($username) ||
									$player->isTier3()
								) {
									$entity->onInteract($player, $clickPos);
								} else {
									$e->cancel();
								}
							} else {
								$entity->onInteract($player, $clickPos);
							}
						}
						if ($entity instanceof Cow || $entity instanceof Chicken) {
							$entity->onInteract($player, $clickPos);
						}
						break;
				}
			}
		}
	}
}
