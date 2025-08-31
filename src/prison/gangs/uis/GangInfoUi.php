<?php namespace prison\gangs\uis;

use pocketmine\player\Player;

use prison\Prison;
use prison\PrisonPlayer;
use prison\gangs\objects\{
	Gang,
	GangMember
};

use core\ui\windows\SimpleForm;
use core\ui\elements\simpleForm\Button;
use core\utils\TextFormat;

class GangInfoUi extends SimpleForm{

	public $gang;

	public $buttons = [];

	public function __construct(Player $player, Gang $gang, string $message = "", bool $error = true){
		$this->gang = $gang;

		parent::__construct(
			"Gang Information",
			($message == "" ? "" : ($error ? TextFormat::RED : TextFormat::GREEN) . $message . TextFormat::WHITE . PHP_EOL . PHP_EOL) .
			"Name: " . $gang->getName() . PHP_EOL .
			"Description: " . $gang->getDescription() . PHP_EOL . PHP_EOL .
			"Level: " . $gang->getLevel() . PHP_EOL .
			"Trophies: " . $gang->getTrophies() . PHP_EOL . PHP_EOL .
			"Kills: " . $gang->getKills() . PHP_EOL .
			"Deaths: " . $gang->getDeaths() . PHP_EOL .
			"Blocks mined: " . number_format($gang->getBlocks()) . PHP_EOL . PHP_EOL .
			"Bank: " . number_format($gang->getBankValue()) . " techits" . PHP_EOL . PHP_EOL .
			"Members: " . PHP_EOL . $gang->getMemberManager()->getOrderedMemberList() . PHP_EOL .
			"Select an option below for more information!"
		);

		$gm = Prison::getInstance()->getGangs()->getGangManager();
		$pgang = $gm->getPlayerGang($player);

		if($pgang !== null && $pgang->getId() == $gang->getId()){
			if($gang->getMemberManager()->getMember($player)->getRole() >= GangMember::ROLE_CO_LEADER){
				$this->addButton(new Button("Edit description"));
			}
		}
	}

	public function handle($response, Player $player) {
		/** @var PrisonPlayer $player */
		$gang = $this->gang;
		$gm = Prison::getInstance()->getGangs()->getGangManager();
		$pgang = $gm->getPlayerGang($player);

		if($pgang != null && $pgang->getId() == $gang->getId()){
			if($gang->getMemberManager()->getMember($player)->getRole() >= GangMember::ROLE_CO_LEADER){
				if($response == 0){
					$player->showModal(new GangDescriptionUi($player, true));
					return;
				}
			}
		}
	}

}