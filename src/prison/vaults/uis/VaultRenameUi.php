<?php namespace prison\vaults\uis;

use pocketmine\player\Player;

use prison\PrisonPlayer;

use core\ui\elements\customForm\{
	Label,
	Input
};
use core\ui\windows\CustomForm;

use core\utils\TextFormat;

class VaultRenameUi extends CustomForm{

	public $player;
	public $vault;

	public function __construct(Player $player, $vault){
		/** @var PrisonPlayer $player */
		$this->player = $player;
		$session = $player->getGameSession()->getVaults();
		$this->vault = $vault;

		parent::__construct("Rename Vault #" . $vault);

		$this->addElement(new Label("What would you like to name this vault? To rename it, type the new name in the box below, then press 'Submit'" . PHP_EOL . PHP_EOL . "Vault name must be at most 16 characters."));
		$this->addElement(new Input("New name", "Loadout", ""));
	}

	public function handle($response, Player $player) {
		/** @var PrisonPlayer $player */
		$session = $player->getGameSession()->getVaults();
		$vault = $session->getVault($this->vault);

		$name = $response[1];
		if(strlen($name) > 16 || $name == null || strlen($name) <= 0){
			$player->sendMessage(TextFormat::RN . "Vault name must be between 0-16 characters!");
			return;
		}

		$vault->setName($name);
		$player->sendMessage(TextFormat::GI . "Successfully renamed Vault #" . $this->vault . " to " . TextFormat::YELLOW . $name);
		$player->showModal(new VaultOptionUi($player, $this->vault));
	}

}