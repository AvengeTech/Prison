<?php namespace prison\mysteryboxes\pieces;

use Ramsey\Uuid\Uuid;

use pocketmine\{
	Server,
	color\Color,
	player\Player
};
use pocketmine\math\Vector3;
use pocketmine\item\{
	VanillaItems,
	Item
};
use pocketmine\block\{
	VanillaBlocks
};
use core\utils\{
    ItemRegistry,
    TextFormat,
};
use pocketmine\network\mcpe\protocol\{
	types\inventory\ItemStackWrapper,
	BlockEventPacket,
	types\BlockPosition,
	LevelSoundEventPacket,
	types\LevelSoundEvent,
	AddPlayerPacket,
	UpdateAbilitiesPacket,
	types\AbilitiesData,
	PlayerListPacket,
	types\PlayerListEntry,
	RemoveActorPacket,
	SetActorDataPacket,

	types\entity\EntityMetadataCollection,
	types\entity\EntityMetadataProperties,
	types\entity\EntityMetadataFlags,
	types\entity\PropertySyncData
};
use pocketmine\network\mcpe\convert\{
    LegacySkinAdapter,
	TypeConverter
};
use pocketmine\world\{
	World,
	sound\PopSound,

	particle\HappyVillagerParticle,
	particle\DustParticle
};
use pocketmine\entity\{
	Entity,
	Skin
};

use prison\PrisonPlayer;
use prison\mysteryboxes\Structure;
use prison\mysteryboxes\uis\OpenBoxUi;
use prison\settings\PrisonSettings;

use core\ui\elements\customForm\Label;
use core\ui\windows\CustomForm;
use core\utils\Utils;
use pocketmine\block\utils\DyeColor;
use pocketmine\network\mcpe\protocol\types\inventory\ItemStack;

class MysteryBox{

	const RENDER_DISTANCE = 35;

	const RARITY_COMMON = 0;
	const RARITY_UNCOMMON = 1;
	const RARITY_RARE = 2;
	const RARITY_LEGENDARY = 3;
	const RARITY_DIVINE = 4;
	const RARITY_VOTE = 5;

	public int $x;
	public int $y;
	public int $z;
	public Vector3 $vector3;
	public string $world;

	public array $categories = [];

	public bool $used = false;
	public string $using = "";

	public bool $opened = false;
	public int $opentick = 0;

	public ?Prize $prize = null;

	public bool $scrolling = false;
	public int $scroll = 0;

	public array $scrollwheel = [];
	public int $scrollwheelkey = 0;

	public int $notekey = 0;
	public array $notes = [15, 22, 27];

	public array $spawnedTo = [];
	public array $entities = [];

	public int|float $t;

	//ft
	public array $texts = [
		1 => [
			"text" => "",
			"eid" => null,
			"pos" => null,
		],
		2 => [
			"text" => "",
			"eid" => null,
			"pos" => null,
		],
		3 => [
			"text" => "",
			"eid" => null,
			"pos" => null,
		],
	];

	public function __construct(public int $id){
		$xyz = Structure::BOX_LOCATIONS[$id];
		$this->x = $xyz[0];
		$this->y = $xyz[1];
		$this->z = $xyz[2];
		$this->vector3 = new Vector3($this->x, $this->y, $this->z);

		$this->world = $xyz[4];

		for($i = 0; $i <= 5; $i++){
			$this->categories[$i] = new RarityCategory($i);
		}

		$this->t = mt_rand(0,100);

		foreach($this->texts as $id => $data){
			$this->texts[$id]["eid"] = Entity::nextRuntimeId();
		}
		$this->texts[1]["pos"] = new Vector3($this->x + 0.5, $this->y + 1, $this->z + 0.5);
		$this->texts[2]["pos"] = new Vector3($this->x + 0.5, $this->texts[1]["pos"]->getY() - 0.3, $this->z + 0.5);
		$this->texts[3]["pos"] = new Vector3($this->x + 0.5, $this->texts[1]["pos"]->getY() - 0.6, $this->z + 0.5);
		$this->updateTexts();
	}

	public function getId() : int{
		return $this->id;
	}

	public function getX() : int{
		return $this->x;
	}

	public function getY() : int{
		return $this->y;
	}

	public function getZ() : int{
		return $this->z;
	}

	public function getVector3() : Vector3{
		return $this->vector3;
	}

	public function getWorld() : World{
		return Server::getInstance()->getWorldManager()->getWorldByName($this->world);
	}

	public function getCategories() : array{
		return $this->categories;
	}

	public function sendUi(Player $player) : void{
		/** @var PrisonPlayer $player */
		$player->showModal(new OpenBoxUi($player, $this));
	}

	public function isUsed() : bool{
		return $this->used;
	}

	public function getUsing() : ?Player{
		return Server::getInstance()->getPlayerExact($this->using);
	}

	public function setUsed(bool $using, ?Player $player = null) : void{
		if($using){
			$this->using = $player->getName();
		}else{
			$this->using = "";
		}
		$this->used = $using;
	}

	public function isOpened() : bool{
		return $this->opened;
	}

	public function getOpenTick() : int{
		return $this->opentick;
	}

	public function getPrize() : ?Prize{
		return $this->prize;
	}

	public function isScrolling() : bool{
		return $this->scrolling;
	}

	public function getScroll() : int{
		return $this->scroll;
	}

	public function getScrollWheel() : array{
		return $this->scrollwheel;
	}

	public function getScrollWheelKey() : int{
		return $this->scrollwheelkey;
	}

	public function getFirstScrollItem() : ?Prize{
		$key = ($this->getScrollWheelKey() - 1 < 0 ? count($this->getScrollWheel()) - 1 : $this->getScrollWheelKey() - 1);
		return $this->getScrollWheel()[$key] ?? null;
	}

	public function getSecondScrollItem() : ?Prize{
		return $this->getScrollWheel()[$this->getScrollWheelKey()] ?? null;
	}

	public function getThirdScrollItem() : ?Prize{
		$key = ($this->getScrollWheelKey() + 1 >= count($this->getScrollWheel()) ? 0 : $this->getScrollWheelKey() + 1);
		return $this->getScrollWheel()[$key] ?? null;
	}

	public function subScrollWheelKey() : void{
		$this->scrollwheelkey--;
		if($this->getScrollWheelKey() < 0) $this->scrollwheelkey = count($this->getScrollWheel()) - 1;
	}

	public function getTier() : string{
		return "none";
	}

	//// EXTERNAL ////
	public function getMaxTick(int $rarity) : int{
		return match($rarity){
			self::RARITY_COMMON => 80,
			self::RARITY_UNCOMMON => 80,
			self::RARITY_RARE => 110,
			self::RARITY_LEGENDARY => 120,
			self::RARITY_DIVINE => 120,
			default => 120
		};
	}

	public function tick() : void{
		if($this->getUsing() != null){
			if($this->getUsing()->getInventory() == null){
				$this->close(true);
				return;
			}
			if($this->isOpened()){
				$this->opentick++;
				$rarity = $this->getPrize()->getRarity();
				$rp = $rarity > 1;

				if($this->opentick <= ($rp ? 50 : 30)){
					if($this->opentick %2 == 0){
						$this->spawnItem($rarity == self::RARITY_DIVINE ? VanillaBlocks::FIRE()->asItem() : null);
					}
				}
				if($rp){
					if($this->opentick == 50){
						if($rarity == self::RARITY_RARE){
							for($i = 0; $i <= 10; $i++){
								$this->spawnItem(VanillaItems::DIAMOND(), false, 1);
							}
						}
						if($rarity == self::RARITY_LEGENDARY){
							for($i = 0; $i <= 20; $i++){
								$this->spawnItem(VanillaItems::EMERALD(), false, 1);
							}
						}
						if($rarity == self::RARITY_DIVINE){
							for($i = 0; $i <= 20; $i++){
								$this->spawnItem(VanillaBlocks::LAVA()->asItem(), false, 1);
							}
						}

					}
					if($rarity == 3) $this->itemParticles();
				}
				if($this->opentick >= $this->getMaxTick($rarity)){
					$this->close();
				}
			}else{
				if($this->isScrolling()){
					$this->t += 0.4;
					$this->moveSwirl();
					$this->scroll--;
					$scroll = $this->getScroll();
					if($scroll <= 0){
						$this->endScroll();
					}else{
						if($scroll > 100){
							$this->updateScroll();
						}elseif($scroll <= 100 && $scroll > 60){
							if($scroll %3 == 0){
								$this->updateScroll();
							}
						}elseif($scroll <= 60 && $scroll > 40){
							if($scroll %5 == 0){
								$this->updateScroll();
							}
						}elseif($scroll <= 40 && $scroll > 20){
							if($scroll %7 == 0){
								$this->updateScroll();
							}
						}elseif($scroll <= 20){
							if($scroll %5000 == 0){
								$this->updateScroll();
							}
						}
					}	
				}
			}
		}else{
			$this->setUsed(false);
			//$this->updateTexts();
		}
	}

	public function moveSwirl() : void{
		$x = cos(0.3 * $this->t);
		$z = sin(0.3 * $this->t);
		$this->getWorld()->addParticle($this->getVector3()->add(0.5 + $x, 1, 0.5 + $z), new DustParticle(new Color(...$this->getSwirlColors())));
	}

	public function getSwirlColors() : array{
		return [16, 255, 64];
	}

	public function getRandomRarity() : int{
		$chance = mt_rand(0, 100);
		$rarity = match(true){
			($chance <= 50) => self::RARITY_COMMON,
			($chance <= 75) => self::RARITY_UNCOMMON,
			($chance <= 90) => self::RARITY_RARE,
			($chance <= 100) => self::RARITY_LEGENDARY 
		};

		return $rarity;
	}

	public function getRandomPrize(array $current = []) : Prize{
		$rarity = $this->getRandomRarity();
		$category = $this->getCategory($rarity);

		$prize = $category->getRandomPrize();

		if(!$prize) return $this->getRandomPrize();

		return $prize;
	}

	public function getCategory(int $rarity) : ?RarityCategory{
		return $this->categories[$rarity] ?? null;
	}

	public function generateScrollWheel() : void{
		for($i = 0; $i <= 30; $i++){
			$this->scrollwheel[] = $this->getRandomPrize($this->scrollwheel);
		}
	}

	public function startScroll(Player $player) : void{
		$this->setUsed(true, $player);

		$this->generateScrollWheel();
		$this->scrolling = true;
		$this->scroll = 120;
	}

	public function updateScroll() : void{
		$this->subScrollWheelKey();

		foreach(Server::getInstance()->getOnlinePlayers() as $p){
			if($p->getPosition()->distance($this->getVector3()) <= 10){
				$pk = new LevelSoundEventPacket();
				$pk->sound = LevelSoundEvent::NOTE;
				$pk->extraData = $this->notes[$this->notekey];
				$pk->position = $this->getVector3();
				$p->getNetworkSession()->sendDataPacket($pk);
			}
		}
		$this->notekey++;
		if($this->notekey >= 3) $this->notekey = 0;

		$this->updateTexts();
	}

	public function endScroll() : void{
		$this->prize = $this->getSecondScrollItem();

		$this->scrolling = false;
		$this->scroll = 0;

		$this->scrollwheel = [];
		$this->scrollwheelkey = 0;

		$this->notekey = 0;

		$this->open();
	}

	public function open() : void{
		$this->entities = [];

		/** @var PrisonPlayer $player */
		$player = $this->getUsing();
		$this->opened = true;
		$this->opentick = 0;

		$this->openChest();

		/** @var PrisonPlayer $p */
		foreach(Server::getInstance()->getOnlinePlayers() as $p){
			if($p->getPosition()->distance($this->getVector3()) <= 10)
				$p->playSound("mob.horse.armor", $this->getVector3(), 75);
		}

		$rarity = $this->prize->getRarity();
		if($rarity > 2){
			if($player->getGameSession()->getSettings()->getSetting(PrisonSettings::MY_MYSTERY_BOX_MESSAGES)){
				foreach(Server::getInstance()->getOnlinePlayers() as $p){
					/** @var PrisonPlayer $p */
					if($p->isLoaded() && $p->getGameSession()->getSettings()->getSetting(PrisonSettings::OTHER_MYSTERY_BOX_MESSAGES)){
						$p->sendMessage(TextFormat::AQUA . TextFormat::BOLD . ">> " . TextFormat::RESET . TextFormat::GRAY . $player->getName() . " found " . $this->getPrize()->getRarityTag() . " " . TextFormat::RESET . TextFormat::AQUA . $this->getPrize()->getName() . TextFormat::GRAY . " in a Mystery Box!");
						$p->playSound(($rarity == 4 ? "lol.bruh" : "random.hurt"), null, 50);
					}
				}
			}
		}

		$session = $player->getGameSession()->getMysteryBoxes();
		$session->takeKeys($this->getTier());
		$session->addOpened();

		if (!$this->prize->givePrize($player, true, ($this->getTier() === "divine"))) {
			$player->sendMessage(TextFormat::RN . "Failed to give the proper prize item! Please report this error, your keys were automatically returned.");
			Utils::dumpVals($this->prize);
			$session->addKeys($this->getTier());
		}

		$this->updateTexts();
	}

	public function openMultiple(Player $player, int $count = 1) : void{
		/** @var PrisonPlayer $player */
		$session = $player->getGameSession()->getMysteryBoxes();
		$originalInventoryCount = $session->getFilter()->getCount();

		$prizes = [];
		for($i = 1; $i <= $count; $i++){
			$prize = $this->getRandomPrize($prizes);
			$prizes[] = $prize;
		}
		$prizestr = "";
		$failures = [];
		foreach($prizes as $prize){
			if (!$prize->givePrize($player, false, ($this->getTier() === "divine"))) $failures[] = $prize;
			else $prizestr .= $prize->getRarityTag() . " " . TextFormat::RESET . TextFormat::AQUA . $prize->getName() . PHP_EOL;
		}


		$form = new CustomForm("MysteryBox opening results");
		$form->addElement(new Label("You used a total of " . TextFormat::YELLOW . $count . " " . $this->getTier() . " keys " . TextFormat::WHITE . "and received the following:"));
		$form->addElement(new Label($prizestr));

		if($session->getFilter()->isEnabled()){
			if($originalInventoryCount < $session->getFilter()->getSize($player->getRank())){
				$form->addElement(new Label(PHP_EOL . TextFormat::YELLOW . ($session->getFilter()->getCount() - $originalInventoryCount) . " rewards " . TextFormat::GRAY . "have been filtered."));
			}else{
				$form->addElement(new Label(PHP_EOL . TextFormat::RED . "Filter Inventory is full!"));
			}
		}
		
		if(count($failures) > 0) {
			$form->addElement(new Label(PHP_EOL . TextFormat::RED . "Failed to give you " . count($failures) . " items! Please report this error, your keys were automatically returned."));
		}
		$player->showModal($form);

		if($session->getFilter()->isAutoClearing()) $session->getFilter(true)->setCount(0); // Just so it can show how many items were filtered out in the Multi Open Keys UI
		$session->takeKeys($this->getTier(), $count);
		$session->addOpened($count);
		if (count($failures) > 0) {
			$session->addKeys($this->getTier(), count($failures));
			Utils::dumpVals($failures);
		}
	}

	public function close(bool $all = false) : void{
		if($all){
			$this->scrolling = false;
			$this->scroll = 0;

			$this->scrollwheel = [];
			$this->scrollwheelkey = 0;
		}
		$this->opened = false;
		$this->opentick = 0;
		$this->prize = null;

		$this->setUsed(false);

		$this->closeChest();

		$this->updateTexts();
	}

	public function openChest() : void{
		$this->chestAction(1,2);
	}

	public function closeChest() : void{
		$this->chestAction(1,0);
	}

	private function chestAction(int $case1, int $case2, array $players = []) : void{
		$pk = new BlockEventPacket();
		$pk->blockPosition = new BlockPosition($this->getX(), $this->getY(), $this->getZ());
		$pk->eventType = $case1;
		$pk->eventData = $case2;

		if(empty($players)) $players = $this->getWorld()->getPlayers();
		foreach($players as $player){
			if($player->getPosition()->distance($this->getVector3()) < 10)
				$player->getNetworkSession()->sendDataPacket($pk);
		}
	}

	public function getColorByRarity(int $rarity) : DyeColor{
		return match($rarity){
			self::RARITY_COMMON => DyeColor::LIME(),
			self::RARITY_UNCOMMON => DyeColor::GREEN(),
			self::RARITY_RARE => DyeColor::YELLOW(),
			self::RARITY_LEGENDARY => DyeColor::getAll()[array_rand(DyeColor::getAll())],
			default => DyeColor::WHITE(),
		};
	}

	public function spawnItem(?Item $item = null, bool $sound = true, float $force = 0.5) : void{
		$yaw = mt_rand(0,360);
		$pitch = ($dc = $this->getTier() == "divine") ? 0 : -84;

		if($item == null) $item = VanillaBlocks::WOOL()->setColor($this->getColorByRarity($this->getPrize()->getRarity()))->asItem();

		$nbt = $item->getNamedTag();
		$nbt->setInt("pickup", 0);
		$item->setNamedTag($nbt);

		$motX = -sin($yaw / 180 * M_PI) * cos($pitch / 180 * M_PI);
		$motY = -sin($pitch / 180 * M_PI);
		$motZ = cos($yaw / 180 * M_PI) * cos($pitch / 180 * M_PI);
		$motV = (new Vector3($motX, $motY, $motZ))->multiply($force);

		$entity = Utils::dropTempItem($this->getWorld(), $this->getVector3()->add(0.5 + ($dc ? $motV->x * 1.2 : 0), ($dc ? 0.4 : 0.95), 0.5 + ($dc ? $motV->z * 1.2 : 0)), $item, $motV, 999, $this->getMaxTick($this->getPrize()->getRarity()));
		$this->entities[] = $entity;

		if($sound) $this->getWorld()->addSound($this->getVector3(), new PopSound());
	}

	public function itemParticles() : void{
		foreach($this->entities as $entity){
			$this->getWorld()->addParticle($entity->getPosition(), new HappyVillagerParticle());
		}
	}

	public function despawnItems() : void{
		foreach($this->entities as $key => $entity){
			// if(!$entity->isFlaggedForDespawn() && !$entity->isClosed()) $entity->flagForDespawn();
		}
	}

	// FT //
	public function sendText(Player $player) : void{
		if(
			!$player->isConnected() ||
			$this->isSpawnedTo($player) ||
			$player->getWorld()->getDisplayName() !== $this->getWorld()->getDisplayName()
		) return;
		$this->spawnedTo[$player->getName()] = true;

		$skin = new Skin("Standard_Custom", str_repeat("\x00", 8192), "", "geometry.humanoid.custom");
		$sa = new LegacySkinAdapter;

		foreach($this->texts as $id => $data){
			$uuid = Uuid::uuid4();

			$pk = new PlayerListPacket();
			$pk->type = PlayerListPacket::TYPE_ADD;
			$pk->entries = [PlayerListEntry::createAdditionEntry($uuid, $id, $data["text"], $sa->toSkinData($skin))];
			$player->getNetworkSession()->sendDataPacket($pk);

			$pk = new AddPlayerPacket();
			$pk->uuid = $uuid;
			$pk->username = $data["text"];
			$pk->actorRuntimeId = $data["eid"];
			$pk->position = $data["pos"]->add(0,0.25,0);
			$pk->item = ItemStackWrapper::legacy(ItemStack::null());
			$pk->gameMode = 0;

			$flags = (
				1 << EntityMetadataFlags::IMMOBILE
			);

			$collection = new EntityMetadataCollection();
			$collection->setLong(EntityMetadataProperties::FLAGS, $flags);
			$collection->setFloat(EntityMetadataProperties::SCALE, 0.01);
			$collection->setString(EntityMetadataProperties::NAMETAG, $data["text"]);
			$collection->setByte(EntityMetadataProperties::ALWAYS_SHOW_NAMETAG, 1);
			$pk->metadata = $collection->getAll();
			$pk->abilitiesPacket = UpdateAbilitiesPacket::create(new AbilitiesData(0, 0, $data["eid"], []));

			$pk->syncedProperties = new PropertySyncData([], []);

			$player->getNetworkSession()->sendDataPacket($pk);

			$pk = new PlayerListPacket();
			$pk->type = PlayerListPacket::TYPE_REMOVE;
			$pk->entries = [PlayerListEntry::createRemovalEntry($uuid)];
			$player->getNetworkSession()->sendDataPacket($pk);
		}
	}

	public function removeText(Player $player) : void{
		if(
			!$player->isConnected() ||
			!$this->isSpawnedTo($player)
		) return;
		unset($this->spawnedTo[$player->getName()]);
		foreach($this->texts as $id => $data){
			$pk = new RemoveActorPacket();
			$pk->actorUniqueId = $data["eid"];
			$player->getNetworkSession()->sendDataPacket($pk);
		}
	}

	public function isSpawnedTo(Player $player) : bool{
		return isset($this->spawnedTo[$player->getName()]);
	}

	public function getSpawnedTo() : array{
		return $this->spawnedTo;
	}

	public function doRenderCheck(Player $player) : void{
		if($this->isSpawnedTo($player)){
			if($this->getVector3()->distance($player->getPosition()) > self::RENDER_DISTANCE){
				$this->removeText($player);
			}
		}else{
			if(
				$player->getWorld() === $this->getWorld() &&
				$this->getVector3()->distance($player->getPosition()) <= self::RENDER_DISTANCE
			){
				$this->sendText($player);
			}
		}
	}

	public function updateTexts() : void{
		$text1 = $text2 = $text3 = "";
		if($this->getUsing() != null){
			if($this->isOpened()){
				$prize = $this->getPrize();
				$text1 = TextFormat::GRAY.$this->getUsing()->getName().",";
				$text2 = TextFormat::GRAY."You found ".$prize->getRarityTag().TextFormat::RESET." ".TextFormat::AQUA.$prize->getShortName();
			}elseif($this->isScrolling() && $this->getFirstScrollItem() != null){
				$i1 = $this->getFirstScrollItem();
				$i2 = $this->getSecondScrollItem();
				$i3 = $this->getThirdScrollItem();

				$text1 = $i1->getRarityTag() . " " . TextFormat::RESET . TextFormat::AQUA . $i1->getShortName();
				$text2 = $i2->getRarityTag() . " " . TextFormat::RESET . TextFormat::AQUA . $i2->getShortName();
				$text3 = $i3->getRarityTag() . " " . TextFormat::RESET . TextFormat::AQUA . $i3->getShortName();

				$len = strlen($text2);
				$space = str_repeat(" ", (60 - (floor($len / 2) * 2)) / 2);
				$text2 = TextFormat::YELLOW.">>>" . $space . $text2;
				$text2 .= $space . TextFormat::YELLOW . "<<<";
			}else{
				$text2 = TextFormat::GRAY.TextFormat::BOLD."Open Mystery Box";
				if($this->getTier() == "iron") $text3 = TextFormat::WHITE . TextFormat::BOLD . "Iron Tier";
				if($this->getTier() == "gold") $text3 = TextFormat::GOLD . TextFormat::BOLD . "Gold Tier";
				if($this->getTier() == "diamond") $text3 = TextFormat::AQUA . TextFormat::BOLD . "Diamond Tier";
				if($this->getTier() == "emerald") $text3 = TextFormat::GREEN . TextFormat::BOLD . "Emerald Tier";
				if($this->getTier() == "divine") $text3 = TextFormat::RED . TextFormat::BOLD . "Divine Tier";
				if($this->getTier() == "vote") $text3 = TextFormat::YELLOW.TextFormat::BOLD . "Vote Tier";
			}
		}else{
			$text2 = TextFormat::GRAY.TextFormat::BOLD."Open Mystery Box";
			if($this->getTier() == "iron") $text3 = TextFormat::WHITE . TextFormat::BOLD . "Iron Tier";
			if($this->getTier() == "gold") $text3 = TextFormat::GOLD . TextFormat::BOLD . "Gold Tier";
			if($this->getTier() == "diamond") $text3 = TextFormat::AQUA . TextFormat::BOLD . "Diamond Tier";
			if($this->getTier() == "emerald") $text3 = TextFormat::GREEN . TextFormat::BOLD . "Emerald Tier";
			if($this->getTier() == "divine") $text3 = TextFormat::RED . TextFormat::BOLD . "Divine Tier";
			if($this->getTier() == "vote") $text3 = TextFormat::YELLOW . TextFormat::BOLD . "Vote Tier";
		}

		$this->texts[1]["text"] = $text1;
		$this->texts[2]["text"] = $text2;
		$this->texts[3]["text"] = $text3;

		foreach($this->getSpawnedTo() as $name => $ok){
			$player = Server::getInstance()->getPlayerExact($name);
			if($player instanceof Player) $this->updateText($player);
		}
	}

	public function updateText(Player $player) : void{
		if(!$this->isSpawnedTo($player) && $player->getWorld()->getDisplayName() !== $this->getWorld()->getDisplayName()) return;
		foreach($this->texts as $id => $data){
			$pk = new SetActorDataPacket();
			$pk->actorRuntimeId = $data["eid"];
			$flags = (
				1 << EntityMetadataFlags::IMMOBILE
			);
			$collection = new EntityMetadataCollection();
			$collection->setLong(EntityMetadataProperties::FLAGS, $flags);
			$collection->setFloat(EntityMetadataProperties::SCALE, 0.01);
			$collection->setString(EntityMetadataProperties::NAMETAG, $data["text"]);
			$collection->setByte(EntityMetadataProperties::ALWAYS_SHOW_NAMETAG, 1);
			$pk->metadata = $collection->getAll();

			$pk->syncedProperties = new PropertySyncData([], []);

			$player->getNetworkSession()->sendDataPacket($pk);
		}
	}

}