<?php namespace prison\vaults\uis;

use pocketmine\player\Player;

use prison\PrisonPlayer;

use core\ui\elements\simpleForm\Button;
use core\ui\windows\SimpleForm;

class VaultOptionUi extends SimpleForm{

	public $player;
	public $vault;

	public function __construct(Player $player, $vault) {
		/** @var PrisonPlayer $player */
		$this->player = $player;

		$session = $player->getGameSession()->getVaults();
		$this->vault = $vault;

		$v = $session->getVault($vault);
		$dumpable = 27 - count($v->getItems());

		parent::__construct($v->getName(), "What would you like to do with this vault?");
		$this->addButton(new Button("Open"));
		$this->addButton(new Button("Rename Vault"));
		//$this->addButton(new Button("Dump " . $dumpable . " items from inventory"));
		//$this->addButton(new Button("Take all"));
		$this->addButton(new Button("Go back"));
	}

	public function handle($response, Player $player) {
		/** @var PrisonPlayer $player */
		$session = $player->getGameSession()->getVaults();
		$vault = $session->getVault($this->vault);

		if($response == 0){
			$vault->open();
			return;
		}
		if($response == 1){
			$player->showModal(new VaultRenameUi($player, $this->vault));
			return;
		}
		$player->showModal(new VaultSelectUi($player));
	}

}