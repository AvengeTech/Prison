<?php namespace prison\data;

use pocketmine\data\bedrock\EffectIdMap;
use pocketmine\entity\effect\{
	Effect,
	EffectInstance,
	VanillaEffects
};
use pocketmine\item\Item;
use pocketmine\nbt\BigEndianNbtSerializer;
use pocketmine\nbt\TreeRoot;
use pocketmine\nbt\tag\{
	CompoundTag,
	IntTag,
	ByteTag
};
use pocketmine\player\Player;

use prison\Prison;
use prison\gangs\battle\BattleKit;

use core\utils\{
	NewSaveableSession,
	InstantLoad
};

/**
 * @deprecated 1.9.0
 */
class Session extends NewSaveableSession implements InstantLoad{

	public $inventory = [];
	public $armorinventory = [];
	public $effects = [];

	public $health = 20;
	public $food = 20;

	public $xplevel = 0;
	public $xpprogress = 0;

	public function load() : void{
		parent::load();
		$db = Prison::getInstance()->getDatabase();
		$xuid = $this->getXuid();

		$stmt = $db->prepare("SELECT data FROM playerdata WHERE xuid=?");
		$stmt->bind_param("i", $xuid);
		$stmt->bind_result($data);
		if($stmt->execute()){
			$stmt->fetch();
		}
		$stmt->close();

		if($data == null) return;

		$data = unserialize(zlib_decode($data));
		$stream = new BigEndianNbtSerializer();
		foreach($data["inventory"] as $slot => $buffer){
			$stream->read($buffer);
			$item = Item::nbtDeserialize($stream->read($buffer)->mustGetCompoundTag());
			if($item->getNamedTag()->getInt(BattleKit::BATTLE_TAG, 0) == 0)
				$this->inventory[$slot] = $item;
		}
		foreach($data["armorinventory"] as $slot => $buffer){
			$item = Item::nbtDeserialize($stream->read($buffer)->mustGetCompoundTag());
			if($item->getNamedTag()->getInt(BattleKit::BATTLE_TAG, 0) == 0)
				$this->armorinventory[$slot] = $item;
		}
		foreach($data["effects"] as $effect){
			if($effect->getInt("id") == EffectIdMap::getInstance()->toId(VanillaEffects::HASTE())){
				$this->effects[] = new EffectInstance(EffectIdMap::getInstance()->fromId($effect->getInt("id")), min(20 * 60 * 60, $effect->getInt("duration")), $effect->getInt("amplifier"), (bool) $effect->getByte("visible"));
			}else{
				$this->effects[] = new EffectInstance(EffectIdMap::getInstance()->fromId($effect->getInt("id")), $effect->getInt("duration"), $effect->getInt("amplifier"), (bool) $effect->getByte("visible"));
			}
		}

		$this->health = $data["health"];
		$this->food = $data["food"];

		$this->xplevel = $data["xplevel"];
		$this->xpprogress = $data["xpprogress"];

		$this->give();
	}

	public function give() : void{
		$player = $this->getPlayer();
		if(!$player instanceof Player) return;

		$player->getInventory()->setContents($this->getInventoryContents());
		$player->getArmorInventory()->setContents($this->getArmorContents());
		foreach($this->getEffects() as $effect){
			if(!in_array(
				EffectIdMap::getInstance()->toId($effect->getType()), [
					EffectIdMap::getInstance()->toId(VanillaEffects::SPEED()),
					EffectIdMap::getInstance()->toId(VanillaEffects::JUMP_BOOST()),
					EffectIdMap::getInstance()->toId(VanillaEffects::NIGHT_VISION())
				])
			) $player->addEffect($effect);
		}

		$player->setHealth($player->getHealth());
		$player->getHungerManager()->setFood($this->getFood());

		$player->getXpManager()->setXpLevel($this->getXpLevel());
		$player->getXpManager()->setXpProgress($this->getXpProgress());

		Prison::getInstance()->getEnchantments()->calculateCache($player);
	}

	public function update() : void{
		$player = $this->getPlayer();

		$this->inventory = $player->getInventory()->getContents();
		$this->armorinventory = $player->getArmorInventory()->getContents();
		$this->effects = $player->getEffects()->all();

		$this->health = $player->getHealth();
		$this->food = $player->getHungerManager()->getFood();

		$this->xplevel = $player->getXpManager()->getXpLevel();
		$this->xpprogress = $player->getXpManager()->getXpProgress();
	}

	public function getInventoryContents() : array{
		return $this->inventory;
	}

	public function getArmorContents() : array{
		return $this->armorinventory;
	}

	public function getEffects() : array{
		return $this->effects;
	}

	public function getHealth() : int{
		return $this->health;
	}

	public function getFood() : int{
		return $this->food;
	}

	public function getXpLevel() : int{
		return $this->xplevel;
	}

	public function getXpProgress() : int{
		return $this->xpprogress;
	}

	public function toString() : string{
		$data = [
			"inventory" => [],
			"armorinventory" => [],
			"effects" => [],

			"health" => $this->getHealth(),
			"food" => $this->getFood(),

			"xplevel" => $this->getXpLevel(),
			"xpprogress" => $this->getXpProgress()
		];
		$stream = new BigEndianNbtSerializer();
		foreach($this->getInventoryContents() as $slot => $item){
			$data["inventory"][$slot] = $stream->write(new TreeRoot($item->nbtSerialize()));
		}
		foreach($this->getArmorContents() as $slot => $item){
			$data["armorinventory"][$slot] = $stream->write(new TreeRoot($item->nbtSerialize()));
		}
		foreach($this->getEffects() as $effect){
			$data["effects"][] = CompoundTag::create()->setInt("id", EffectIdMap::getInstance()->toId($effect->getType()))->setInt("duration", $effect->getDuration())->setInt("amplifier", $effect->getAmplifier())->setByte("visible", $effect->isVisible());
		}

		return zlib_encode(serialize($data), ZLIB_ENCODING_DEFLATE, 1);
	}

	public function save() : void{
		$this->update();

		$data = $this->toString();
		$xuid = $this->getXuid();

		$db = Prison::getInstance()->getDatabase();
		$stmt = $db->prepare("INSERT INTO playerdata(xuid, data) VALUES(?, ?) ON DUPLICATE KEY UPDATE data=VALUES(data)");
		$stmt->bind_param("is", $xuid, $data);
		$stmt->execute();
		$stmt->close();
	}

	public function __toString() : string{
		return $this->toString();
	}

}