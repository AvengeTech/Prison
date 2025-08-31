<?php namespace prison;

use pocketmine\player\Player;

use prison\{
	blocktournament\BlockTournamentComponent,
	cells\CellsComponent,
	combat\CombatComponent,
	data\DataComponent,
	guards\GuardsComponent,
	kits\KitsComponent,
	koth\KothComponent,
	mines\MinesComponent,
	mysteryboxes\MysteryBoxesComponent,
	quests\QuestsComponent,
	rankup\RankUpComponent,
	shops\ShopsComponent,
	tags\TagsComponent,
	techits\TechitsComponent,
	trade\TradeComponent,
	vaults\VaultsComponent,
};
use prison\settings\PrisonSettings;

use core\session\{
	PlayerSession,
	SessionManager
};
use core\settings\SettingsComponent;
use core\user\User;
use core\utils\Version;
use prison\enchantments\EssenceComponent;
use prison\fishing\FishingComponent;
use prison\skills\SkillsComponent;

class PrisonSession extends PlayerSession{

	public function __construct(SessionManager $sessionManager, Player|User $user){
		parent::__construct($sessionManager, $user);

		$this->addComponent(new BlockTournamentComponent($this));
		$this->addComponent(new CellsComponent($this));
		$this->addComponent(new CombatComponent($this));
		$this->addComponent(new DataComponent($this));
		$this->addComponent(new EssenceComponent($this));
		$this->addComponent(new FishingComponent($this));
		$this->addComponent(new GuardsComponent($this));
		$this->addComponent(new KitsComponent($this));
		$this->addComponent(new KothComponent($this));
		$this->addComponent(new MinesComponent($this));
		$this->addComponent(new MysteryBoxesComponent($this));
		$this->addComponent(new QuestsComponent($this));
		$this->addComponent(new RankUpComponent($this));
		$this->addComponent(new ShopsComponent($this));
		// $this->addComponent(new SkillsComponent($this));
		$this->addComponent(new TagsComponent($this));
		$this->addComponent(new TechitsComponent($this));
		$this->addComponent(new TradeComponent($this));
		$this->addComponent(new VaultsComponent($this));

		$this->addComponent(new SettingsComponent(
			$this,
			Version::fromString(PrisonSettings::VERSION),
			PrisonSettings::DEFAULT_SETTINGS,
			PrisonSettings::SETTING_UPDATES
		));
	}

	public function getBlockTournament() : BlockTournamentComponent{
		return $this->getComponent("blocktournament");
	}

	public function getCells() : CellsComponent{
		return $this->getComponent("cells");
	}

	public function getCombat() : CombatComponent{
		return $this->getComponent("combat");
	}

	public function getData() : DataComponent{
		return $this->getComponent("data");
	}

	public function getEssence() : EssenceComponent{
		return $this->getComponent("essence");
	}

	public function getFishing() : FishingComponent{
		return $this->getComponent("fishing");
	}

	public function getGuards() : GuardsComponent{
		return $this->getComponent("guards");
	}

	public function getKits() : KitsComponent{
		return $this->getComponent("kits");
	}

	public function getKoth() : KothComponent{
		return $this->getComponent("koth");
	}

	public function getMines() : MinesComponent{
		return $this->getComponent("mines");
	}

	public function getMysteryBoxes() : MysteryBoxesComponent{
		return $this->getComponent("mysteryboxes");
	}

	public function getQuests() : QuestsComponent{
		return $this->getComponent("quests");
	}

	public function getRankUp() : RankUpComponent{
		return $this->getComponent("rankup");
	}

	public function getShops() : ShopsComponent{
		return $this->getComponent("shops");
	}

	public function getSkills() : SkillsComponent{
		return $this->getComponent("skills");
	}

	public function getTags() : TagsComponent{
		return $this->getComponent("tags");
	}

	public function getTechits() : TechitsComponent{
		return $this->getComponent("techits");
	}
	
	public function getTrade() : TradeComponent{
		return $this->getComponent("trade");
	}

	public function getVaults() : VaultsComponent{
		return $this->getComponent("vaults");
	}

	public function getSettings() : SettingsComponent{
		return $this->getComponent("settings");
	}

}