<?php namespace prison\vaults\uis;

use pocketmine\player\Player;

use prison\PrisonPlayer;

use core\ui\elements\simpleForm\Button;
use core\ui\windows\SimpleForm;

use core\utils\TextFormat;
use core\network\Links;

class VaultSelectUi extends SimpleForm{

	public $player;
	public $vaults = [];

	public function __construct(Player $player){
		/** @var PrisonPlayer $player */
		$this->player = $player;
		$session = $player->getGameSession()->getVaults();

		parent::__construct("Private Vaults", "Select a vault! You have " . $session->getVaultCount() . "/" . $session->getMaxVaults() . " vaults unlocked! Unlock more by purchasing a rank at " . TextFormat::YELLOW . Links::SHOP);

		for($i = 1; $i <= $session->getVaultCount(); $i++){
			$vault = $session->getVault($i);
			if($vault !== null){
				$this->vaults[$i] = $vault;
				$this->addButton(new Button($vault->getId() . ". " . $vault->getName() . PHP_EOL . "(" . count($vault->getItems()) . "/54)"));
			}
		}
		if(count($this->vaults) < $session->getMaxVaults()){
			for($i = count($this->vaults) + 1; $i <= $session->getMaxVaults(); $i++){
				$this->addButton(new Button($i . ". Vault #" . $i . PHP_EOL . TextFormat::RED . TextFormat::BOLD . "LOCKED"));
			}
		}

	}

	public function handle($response, Player $player){
		/** @var PrisonPlayer $player */
		$session = $player->getGameSession()->getVaults();
		if($response >= $session->getVaultCount()){
			$player->sendMessage(TextFormat::RN . "You must upgrade your rank to access more vaults! Upgrade your rank at " . TextFormat::YELLOW . Links::SHOP);
			return;
		}
		foreach($this->vaults as $key => $vault){
			if($key == $response + 1){
				$player->showModal(new VaultOptionUi($player, $vault->getId()));
				return;
			}
		}
	}

}