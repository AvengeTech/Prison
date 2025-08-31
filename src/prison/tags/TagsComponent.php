<?php namespace prison\tags;

use pocketmine\player\Player;

use prison\Prison;

use core\session\component\{
	ComponentRequest,
	SaveableComponent
};
use core\session\mysqli\data\MySqlQuery;

class TagsComponent extends SaveableComponent{

	public ?Tag $active = null;
	public array $tags = [];

	public function getName() : string{
		return "tags";
	}

	public function getActiveTag() : ?Tag{
		return $this->active;
	}

	public function setActiveTag(?Tag $tag = null) : void{
		if($tag instanceof Tag) $tag = clone $tag;

		$this->active = $tag;

		$player = $this->getPlayer();
		if($player instanceof Player){
			$player->updateNametag();
			$player->updateChatFormat();
		}
		$this->setChanged();
	}

	public function getTagsNoHave() : array{
		$no = [];
		$tags = Prison::getInstance()->getTags()->getTags();
		foreach($tags as $tag){
			if(!$this->hasTag($tag) && !$tag->isDisabled())
				$no[] = $tag;
		}
		return $no;
	}

	public function getTags() : array{
		return $this->tags;
	}

	public function hasTag(Tag $tag) : bool{
		return isset($this->tags[$tag->getName()]);
	}

	public function addTag(Tag $tag) : void{
		$this->tags[$tag->getName()] = clone $tag;
		$this->setChanged();
	}

	public function removeTag(Tag $tag) : void{
		unset($this->tags[$tag->getName()]);
		$this->setChanged();
	}

	public function createTables() : void{
		$db = $this->getSession()->getSessionManager()->getDatabase();
		foreach([
			"CREATE TABLE IF NOT EXISTS tags_data(xuid BIGINT(16) NOT NULL UNIQUE, active VARCHAR(50), tags TEXT)"
		] as $query) $db->query($query);
	}

	public function loadAsync() : void{
		$request = new ComponentRequest($this->getXuid(), $this->getName(), new MySqlQuery("main", "SELECT * FROM tags_data WHERE xuid=?", [$this->getXuid()]));
		$this->newRequest($request, ComponentRequest::TYPE_LOAD);
		parent::loadAsync();
	}

	public function finishLoadAsync(?ComponentRequest $request = null) : void{
		$result = $request->getQuery()->getResult();
		$rows = (array) $result->getRows();
		if(count($rows) > 0){
			$data = array_shift($rows);
			$active = $this->active = Prison::getInstance()->getTags()->getTag($data["active"]);
			$tags = explode(",", $data["tags"]);
			foreach($tags as $tag){
				$tagc = Prison::getInstance()->getTags()->getTag($tag);
				if($tagc !== null){
					$this->tags[$tagc->getName()] = $tagc;
				}
			}
		}
		
		parent::finishLoadAsync($request);
	}

	/*public function load() : bool{
		return parent::load();
	}*/

	public function verifyChange() : bool{
		$player = $this->getPlayer();
		$verify = $this->getChangeVerify();
		return $this->getTags() !== $verify["tags"];
	}

	public function saveAsync() : void{
		if(!$this->hasChanged() || !$this->isLoaded()) return;

		$this->setChangeVerify([
			"tags" => $this->getTags(),
		]);

		$active = $this->getActiveTag();
		$active = $active === null ? "" : $active->getName();
		$tags = $this->getTags();
		$tl = [];
		foreach($tags as $name => $tag){
			$tl[] = $name;
		}
		$tags = implode(",", $tl);

		$player = $this->getPlayer();
		$request = new ComponentRequest($this->getXuid(), $this->getName(), new MySqlQuery("main", "INSERT INTO tags_data(xuid, active, tags) VALUES(?, ?, ?) ON DUPLICATE KEY UPDATE active=VALUES(active), tags=VALUES(tags)", [$this->getXuid(), $active, $tags]));
		$this->newRequest($request, ComponentRequest::TYPE_SAVE);
		parent::saveAsync();
	}

	public function save() : bool{
		if(!$this->hasChanged() || !$this->isLoaded()) return false;

		$xuid = $this->getXuid();
		$active = $this->getActiveTag();
		$active = $active === null ? "" : $active->getName();
		$tags = $this->getTags();
		$tl = [];
		foreach($tags as $name => $tag){
			$tl[] = $name;
		}
		$tags = implode(",", $tl);

		$db = $this->getSession()->getSessionManager()->getDatabase();
		$stmt = $db->prepare("INSERT INTO tags_data(xuid, active, tags) VALUES(?, ?, ?) ON DUPLICATE KEY UPDATE active=VALUES(active), tags=VALUES(tags)");
		$stmt->bind_param("iss", $xuid, $active, $tags);
		$stmt->execute();
		$stmt->close();

		return parent::save();
	}

	public function getSerializedData(): array {
		$active = $this->getActiveTag();
		$active = $active === null ? "" : $active->getName();
		$tags = $this->getTags();
		$tl = [];
		foreach ($tags as $name => $tag) {
			$tl[] = $name;
		}
		$tags = implode(",", $tl);

		return [
			"active" => $active,
			"tags" => $tags
		];
	}

	public function applySerializedData(array $data): void {
		$this->active = Prison::getInstance()->getTags()->getTag($data["active"]);
		$tags = explode(",", $data["tags"]);
		foreach ($tags as $tag) {
			$tagc = Prison::getInstance()->getTags()->getTag($tag);
			if ($tagc !== null) {
				$this->tags[$tagc->getName()] = $tagc;
			}
		}
	}

}