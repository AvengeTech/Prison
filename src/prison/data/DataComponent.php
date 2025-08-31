<?php namespace prison\data;

use pocketmine\data\bedrock\EffectIdMap;
use pocketmine\entity\effect\{
	EffectInstance,
	VanillaEffects
};
use pocketmine\item\Item;
use pocketmine\nbt\BigEndianNbtSerializer;
use pocketmine\nbt\TreeRoot;
use pocketmine\nbt\tag\{
	CompoundTag,
};
use pocketmine\player\Player;

use prison\Prison;
use prison\gangs\battle\BattleKit;

use core\session\component\{
	ComponentRequest,
	SaveableComponent
};
use core\session\mysqli\data\MySqlQuery;

class DataComponent extends SaveableComponent{

	public array $inventory = [];
	public array $armorinventory = [];
	public array $enderchest_inventory = [];
	public array $effects = [];

	public int $health = 20;
	public int $food = 20;
	public int $saturation = 20;

	public int $xplevel = 0;
	public float $xpprogress = 0;

	public function getName() : string{
		return "data";
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

		$player->getSession()?->getSeeInv()?->pullFromPlayer();
	}

	public function update(): void {
		$player = $this->getPlayer();
		if (!$player instanceof Player || !$player->isLoaded()) return;

		$this->inventory = $player->getInventory()->getContents();
		$this->armorinventory = $player->getArmorInventory()->getContents();
		$this->enderchest_inventory = $player->getEnderInventory()->getContents();
		$this->effects = $player->getEffects()->all();

		$this->health = (int) $player->getHealth();
		$this->food = (int) $player->getHungerManager()->getFood();
		$this->saturation = (int) $player->getHungerManager()->getSaturation();

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

	public function getXpProgress() : float{
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

	public function createTables() : void{
		$db = $this->getSession()->getSessionManager()->getDatabase();
		foreach([
			// "DROP TABLE IF EXISTS playerdata",
			"CREATE TABLE IF NOT EXISTS playerdata(xuid BIGINT(16) NOT NULL UNIQUE, data LONGBLOB NOT NULL)",
		] as $query) $db->query($query);
	}

	public function loadAsync() : void{
		$request = new ComponentRequest($this->getXuid(), $this->getName(), new MySqlQuery("main", "SELECT data FROM playerdata WHERE xuid=?", [$this->getXuid()]));
		$this->newRequest($request, ComponentRequest::TYPE_LOAD);
		parent::loadAsync();
	}

	public function finishLoadAsync(?ComponentRequest $request = null) : void{
		$result = $request->getQuery()->getResult();
		$rows = (array) $result->getRows();
		if(count($rows) > 0){
			$data = array_shift($rows);
			$data = $data["data"];

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
		}

		parent::finishLoadAsync($request);
	}

	public function saveAsync() : void{
		if(!$this->isLoaded()) return;
		
		$this->update();

		$request = new ComponentRequest($this->getXuid(), $this->getName(), new MySqlQuery("main", "INSERT INTO playerdata(xuid, data) VALUES(?, ?) ON DUPLICATE KEY UPDATE data=VALUES(data)", [$this->getXuid(), $this->toString()]));
		$this->newRequest($request, ComponentRequest::TYPE_SAVE);
		parent::saveAsync();
	}

	public function save(bool $update = true) : bool{
		if(!$this->isLoaded()) return false;
		
		if ($update) $this->update();

		$xuid = $this->getXuid();
		$data = $this->toString();

		$db = $this->getSession()->getSessionManager()->getDatabase();

		$stmt = $db->prepare("INSERT INTO playerdata(xuid, data) VALUES(?, ?) ON DUPLICATE KEY UPDATE data=VALUES(data)");
		$stmt->bind_param("is", $xuid, $data);
		$stmt->execute();
		$stmt->close();

		return parent::save();
	}

	public function getSerializedData(): array {
		return [
			"data" => $this->toString()
		];
	}

	public function applySerializedData(array $data): void {
		$data = $data["data"];

		$data = unserialize(zlib_decode($data));
		$stream = new BigEndianNbtSerializer();
		foreach ($data["inventory"] as $slot => $buffer) {
			$stream->read($buffer);
			$item = Item::nbtDeserialize($stream->read($buffer)->mustGetCompoundTag());
			if ($item->getNamedTag()->getInt(BattleKit::BATTLE_TAG, 0) == 0)
				$this->inventory[$slot] = $item;
		}
		foreach ($data["armorinventory"] as $slot => $buffer) {
			$item = Item::nbtDeserialize($stream->read($buffer)->mustGetCompoundTag());
			if ($item->getNamedTag()->getInt(BattleKit::BATTLE_TAG, 0) == 0)
				$this->armorinventory[$slot] = $item;
		}
		foreach ($data["effects"] as $effect) {
			if ($effect->getInt("id") == EffectIdMap::getInstance()->toId(VanillaEffects::HASTE())) {
				$this->effects[] = new EffectInstance(EffectIdMap::getInstance()->fromId($effect->getInt("id")), min(20 * 60 * 60, $effect->getInt("duration")), $effect->getInt("amplifier"), (bool) $effect->getByte("visible"));
			} else {
				$this->effects[] = new EffectInstance(EffectIdMap::getInstance()->fromId($effect->getInt("id")), $effect->getInt("duration"), $effect->getInt("amplifier"), (bool) $effect->getByte("visible"));
			}
		}

		$this->health = $data["health"];
		$this->food = $data["food"];

		$this->xplevel = $data["xplevel"];
		$this->xpprogress = $data["xpprogress"];
	}

}