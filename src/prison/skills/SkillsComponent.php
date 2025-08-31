<?php namespace prison\skills;

use core\inbox\object\InboxInstance;
use core\inbox\object\MessageInstance;
use core\session\component\ComponentRequest;
use core\session\component\SaveableComponent;
use core\session\mysqli\data\MySqlQuery;
use core\utils\TextFormat;
use pocketmine\item\Armor;
use pocketmine\item\VanillaArmorMaterials;
use prison\PrisonPlayer;

class SkillsComponent extends SaveableComponent{

	const COST_PER_FIVE_PRESTIGE = 500000;
	const COST_PER_PRESTIGE = 1000000;
	const MAX_SKILL_LEVEL = 110;

	private int $prestige = 0;
	/** @var Skill[] $skills */
	private array $skills = [];



	public function getName() : string{
		return 'skills';
	}

	/**
	 * @return Skill[]
	 */
	public function getSkills() : array{
		return $this->skills;
	}

	public function getSkill(string $identifier) : Skill{
		return $this->skills[$identifier];
	}

	public function getPrestige() : int{
		return $this->prestige;
	}

	public function addPrestige(int $prestige) : void{
		$this->setPrestige($this->getPrestige() + $prestige);
	}

	public function reducePrestige(int $prestige) : void{
		$this->setPrestige($this->getPrestige() - $prestige);
	}

	public function setPrestige(int $prestige) : void{
		$this->prestige = $prestige;

		$this->setChanged();
	}

	public function canPrestige() : bool{
		foreach($this->getSkills() as $skill){
			if($skill->getLevel() < $this->getMaxSkillLevel()) return false;
		}

		return true;
	}

	public function prestige() : void{
		if(!($this->canPrestige())) return;

		foreach($this->getSkills() as $skill){
			$skill->setLevel(1);
			$skill->setExperience(0);
		}

		$this->addPrestige(1);

		/** @var PrisonPlayer $player */
		$player = $this->getPlayer();
		$player->takeTechits($this->getPrestigeCost());
		$player->getGameSession()->getMysteryBoxes()->addKeys("divine", 2);

		$items = [];

		foreach($player->getArmorInventory()->getContents() as $item){
			if($item instanceof Armor && $item->getMaterial() === VanillaArmorMaterials::LEATHER()) continue;

			if($player->getInventory()->canAddItem($item)){
				$player->getInventory()->addItem($item);

				$player->sendMessage(TextFormat::GI . "Your armor was taken off and sent to your inventory when you prestiged all your skills!");
			}else{
				$items[] = $item;
			}

			$player->getArmorInventory()->removeItem($item);
		}

		if(!(empty($items))){
			$inbox = new InboxInstance($player->getUser(), "here");
			$msg = new MessageInstance($inbox, MessageInstance::newId(), time(), 0, "No Inventory Space", "You had not space in your inventory when you prestiged your skills!", false);
			$msg->setItems($items);
			$inbox->addMessage($msg, true);

			$player->sendMessage(TextFormat::GI . "Your armor take was sent to your inbox because your inventory was full when you prestiged all your skills!");
		}
	}

	public function getPrestigeCost() : int{
		$needed = ($this->getPrestige() + 1) * self::COST_PER_PRESTIGE;

		if($this->getPrestige() + 1 > 5){
			$needed = round($this->getPrestige() + 1 / 5, PHP_ROUND_HALF_DOWN) * self::COST_PER_FIVE_PRESTIGE;
		}

		return $needed;
	}

	public function getMaxSkillLevel() : int{
		return self::MAX_SKILL_LEVEL + (10 * $this->getPrestige());
	}

	public function createTables() : void{
		$db = $this->getSession()->getSessionManager()->getDatabase();

		foreach([
			"CREATE TABLE IF NOT EXISTS skills_data(
				xuid BIGINT(16) NOT NULL UNIQUE, prestige INT NOT NULL DEFAULT 0,
				mining_level INT NOT NULL DEFAULT 1, mining_xp INT NOT NULL DEFAULT 0,
				combat_level INT NOT NULL DEFAULT 1, combat_xp INT NOT NULL DEFAULT 0,
				farming_level INT NOT NULL DEFAULT 1, farming_xp INT NOT NULL DEFAULT 0,
				axe_combat_level INT NOT NULL DEFAULT 1, axe_combat_xp INT NOT NULL DEFAULT 0,
				fishing_level INT NOT NULL DEFAULT 1, fishing_xp INT NOT NULL DEFAULT 0
			)"
		] as $query) $db->query($query);
	}

	public function loadAsync() : void{
		$request = new ComponentRequest($this->getXuid(), $this->getName(), [
			new MySqlQuery("main", "SELECT * FROM skills_data where xuid=?", [$this->getXuid()]),
		]);
		$this->newRequest($request, ComponentRequest::TYPE_LOAD);
		parent::loadAsync();
	}

	public function finishLoadAsync(?ComponentRequest $request = null) : void{
		$result = $request->getQuery()->getResult();
		$rows = (array) $result->getRows();

		if(count($rows) > 0){
			$data = array_shift($rows);
			$this->prestige = $data['prestige'];

			foreach([
				Skill::SKILL_MINING => [$data['mining_level'], $data['mining_xp']], 
				Skill::SKILL_COMBAT => [$data['combat_level'], $data['combat_xp']], 
				Skill::SKILL_FARMING => [$data['farming_level'], $data['farming_xp']], 
				Skill::SKILL_AXE_COMBAT => [$data['axe_combat_level'], $data['axe_combat_xp']], 
				Skill::SKILL_FISHING => [$data['fishing_level'], $data['fishing_xp']]
			] as $identifier => $data){
				$this->skills[$identifier] = new Skill($identifier, $data[0], $data[1], $this);
			}
		}else{
			foreach([Skill::SKILL_MINING, Skill::SKILL_COMBAT, Skill::SKILL_FARMING, Skill::SKILL_AXE_COMBAT, Skill::SKILL_FISHING] as $identifier){
				$this->skills[$identifier] = new Skill($identifier, 1, 0, $this);
			}
		}

		parent::finishLoadAsync($request);
	}

	public function verifyChange() : bool{
		$verify = $this->getChangeVerify();

		return $this->getSkills() !== $verify['skills'] || $this->getPrestige() !== $verify['prestige'];
	}

	public function saveAsync() : void{
		if(!$this->hasChanged() || !$this->isLoaded()) return;

		$this->setChangeVerify([
			'skills' => $this->getSkills(),
			'prestige' => $this->getPrestige()
		]);

		$request = new ComponentRequest($this->getXuid(), $this->getName(), new MySqlQuery("main",
			"INSERT INTO skills_data(
				xuid, prestige,
				mining_level, mining_xp,
				combat_level, combat_xp,
				farming_level, farming_xp,
				axe_combat_level, axe_combat_xp,
				fishing_level, fishing_xp
			) VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE
				prestige=VALUES(prestige),
				mining_level=VALUES(mining_level), mining_xp=VALUES(mining_xp),
				combat_level=VALUES(combat_level), combat_xp=VALUES(combat_xp),
				farming_level=VALUES(farming_level), farming_xp=VALUES(farming_xp),
				axe_combat_level=VALUES(axe_combat_level), axe_combat_xp=VALUES(axe_combat_xp),
				fishing_level=VALUES(fishing_level), fishing_xp=VALUES(fishing_xp)",
			[
				$this->getXuid(), $this->getPrestige(), 
				$this->getSkill(Skill::SKILL_MINING)->getLevel(), $this->getSkill(Skill::SKILL_MINING)->getExperience(), 
				$this->getSkill(Skill::SKILL_COMBAT)->getLevel(), $this->getSkill(Skill::SKILL_COMBAT)->getExperience(), 
				$this->getSkill(Skill::SKILL_FARMING)->getLevel(), $this->getSkill(Skill::SKILL_FARMING)->getExperience(), 
				$this->getSkill(Skill::SKILL_AXE_COMBAT)->getLevel(), $this->getSkill(Skill::SKILL_AXE_COMBAT)->getExperience(), 
				$this->getSkill(Skill::SKILL_FISHING)->getLevel(), $this->getSkill(Skill::SKILL_FISHING)->getExperience()
			]
		));
		
		$this->newRequest($request, ComponentRequest::TYPE_SAVE);
		parent::saveAsync();
	}

	public function save() : bool{
		if(!$this->hasChanged() || !$this->isLoaded()) return false;

		$xuid = $this->getXuid();
		$prestige = $this->getPrestige();

		$miningLevel = $this->getSkill(Skill::SKILL_MINING)->getLevel();
		$miningXP = $this->getSkill(Skill::SKILL_MINING)->getExperience();
		$combatLevel = $this->getSkill(Skill::SKILL_COMBAT)->getLevel();
		$combatXP = $this->getSkill(Skill::SKILL_COMBAT)->getExperience();
		$farmingLevel = $this->getSkill(Skill::SKILL_FARMING)->getLevel();
		$farmingXP = $this->getSkill(Skill::SKILL_FARMING)->getExperience();
		$axeCombatLevel = $this->getSkill(Skill::SKILL_AXE_COMBAT)->getLevel();
		$axeCombatXP = $this->getSkill(Skill::SKILL_AXE_COMBAT)->getExperience();
		$fishingLevel = $this->getSkill(Skill::SKILL_FISHING)->getLevel();
		$fishingXP = $this->getSkill(Skill::SKILL_FISHING)->getExperience();

		$db = $this->getSession()->getSessionManager()->getDatabase();
		$stmt = $db->prepare(
			"INSERT INTO skills_data(
				xuid, prestige,
				mining_level, mining_xp,
				combat_level, combat_xp,
				farming_level, farming_xp,
				axe_combat_level, axe_combat_xp,
				fishing_level, fishing_xp
			) VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE
				prestige=VALUES(prestige),
				mining_level=VALUES(mining_level), mining_xp=VALUES(mining_xp),
				combat_level=VALUES(combat_level), combat_xp=VALUES(combat_xp),
				farming_level=VALUES(farming_level), farming_xp=VALUES(farming_xp),
				axe_combat_level=VALUES(axe_combat_level), axe_combat_xp=VALUES(axe_combat_xp),
				fishing_level=VALUES(fishing_level), fishing_xp=VALUES(fishing_xp)"
		);
		$stmt->bind_param(
			"iiiiiiiiiiii", 
			$xuid, $prestige, 
			$miningLevel, $miningXP, 
			$combatLevel, $combatXP, 
			$farmingLevel, $farmingXP, 
			$axeCombatLevel, $axeCombatXP, 
			$fishingLevel, $fishingXP
		);
		$stmt->execute();
		$stmt->close();

		return parent::save();
	}

	public function getSerializedData(): array {
		return [
			"prestige" => $this->getPrestige(),
			"mining_level" => $this->getSkill(Skill::SKILL_MINING)->getLevel(),
			"mining_xp" => $this->getSkill(Skill::SKILL_MINING)->getExperience(),
			"combat_level" => $this->getSkill(Skill::SKILL_COMBAT)->getLevel(),
			"combat_xp" => $this->getSkill(Skill::SKILL_COMBAT)->getExperience(),
			"farming_level" => $this->getSkill(Skill::SKILL_FARMING)->getLevel(),
			"farming_xp" => $this->getSkill(Skill::SKILL_FARMING)->getExperience(),
			"axe_combat_level" => $this->getSkill(Skill::SKILL_AXE_COMBAT)->getLevel(),
			"axe_combat_xp" => $this->getSkill(Skill::SKILL_AXE_COMBAT)->getExperience(),
			"fishing_level" => $this->getSkill(Skill::SKILL_FISHING)->getLevel(),
			"fishing_xp" => $this->getSkill(Skill::SKILL_FISHING)->getExperience()
		];
	}

	public function applySerializedData(array $data): void {
		$this->prestige = $data['prestige'];

		foreach (
			[
				Skill::SKILL_MINING => [$data['mining_level'], $data['mining_xp']],
				Skill::SKILL_COMBAT => [$data['combat_level'], $data['combat_xp']],
				Skill::SKILL_FARMING => [$data['farming_level'], $data['farming_xp']],
				Skill::SKILL_AXE_COMBAT => [$data['axe_combat_level'], $data['axe_combat_xp']],
				Skill::SKILL_FISHING => [$data['fishing_level'], $data['fishing_xp']]
			] as $identifier => $data
		) {
			$this->skills[$identifier] = new Skill($identifier, $data[0], $data[1], $this);
		}
	}
}