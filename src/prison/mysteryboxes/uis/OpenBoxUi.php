<?php namespace prison\mysteryboxes\uis;

use core\Core;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

use prison\PrisonPlayer;
use prison\mysteryboxes\pieces\MysteryBox;

use core\ui\windows\SimpleForm;
use core\ui\elements\simpleForm\Button;
use core\network\Links;
use core\rank\Structure;

class OpenBoxUi extends SimpleForm{
	
	public function __construct(Player $player, public MysteryBox $box){
		/** @var PrisonPlayer $player */
		$session = $player->getGameSession()->getMysteryBoxes();
		$keys = $session->getKeys($box->getTier());

		parent::__construct("Mystery Box", "You currently have " . number_format($keys) . " " . $box->getTier() . " keys available. Are you sure you want to open a Mystery Box?");

		$this->addButton(new Button("Use x1 key"));
		$this->addButton(new Button("Use x1 key INSTANTLY"));
		$this->addButton(new Button("Use multiple keys"));

		if($box->getTier() !== "divine") $this->addButton(new Button(TextFormat::BOLD . TextFormat::AQUA . "Use Filter"));
	}

	public function handle($response, Player $player){
		/** @var PrisonPlayer $player */
		$box = $this->box;
		if($response == 0){
			if($box->getUsing() != null){
				$player->sendMessage(TextFormat::RED."This Mystery Box is being used.");
				return;
			}
			$session = $player->getGameSession()->getMysteryBoxes();
			$keys = $session->getKeys($box->getTier());
			if($keys <= 0){
				$text = TextFormat::YELLOW . TextFormat::BOLD . "(i) " . TextFormat::RESET . TextFormat::GRAY . "You don't have any " . $box->getTier() . " keys!";
				if($box->getTier() != "vote"){
					$text .= " Find them by mining, purchase them with Quest Points, or buy them at " . TextFormat::YELLOW . Links::SHOP;
				}else{
					$text .= " Get them for free by voting! Learn how to vote by typing " .TextFormat::YELLOW . "/vote" . TextFormat::GRAY . " in the chat!";
				}
				$player->sendMessage($text);
				return;
			}
			$box->startScroll($player);
			return;
		}elseif($response == 1){
			if($player->getRank() == "default"){
				$player->sendMessage(TextFormat::YELLOW . TextFormat::BOLD . "(i) " . TextFormat::RESET . TextFormat::GRAY . "You need a rank to instantly open Mystery Boxes! Purchase one at " . TextFormat::YELLOW . Links::SHOP);
				return;
			}
			if($box->getUsing() != null){
				$player->sendMessage(TextFormat::YELLOW . TextFormat::BOLD . "(i) " . TextFormat::RESET . TextFormat::GRAY . "This Mystery Box is being used.");
				return;
			}
			$session = $player->getGameSession()->getMysteryBoxes();
			$keys = $session->getKeys($box->getTier());
			if($keys <= 0){
				$text = TextFormat::YELLOW . TextFormat::BOLD . "(i) " . TextFormat::RESET . TextFormat::GRAY . "You don't have any " . $box->getTier() . " keys!";
				if($box->getTier() != "vote"){
					$text .= " Find them by mining, purchase them with Quest Points, or buy them at " . TextFormat::YELLOW . Links::SHOP;
				}else{
					$text .= " Get them for free by voting! Learn how to vote by typing " .TextFormat::YELLOW . "/vote" . TextFormat::GRAY . " in the chat!";
				}
				$player->sendMessage($text);
				return;
			}
			$box->startScroll($player);
			$box->endScroll();
			return;
		}elseif($response == 2){
			$rh = Structure::RANK_HIERARCHY[$player->getRank()];
			if($rh < 5){
				$player->sendMessage(TextFormat::YELLOW . TextFormat::BOLD . "(i) " . TextFormat::RESET . TextFormat::GRAY . "You need at least " . strtoupper(Core::getInstance()->getChat()->getFormattedRank("wither")) . " rank to open multiple Mystery Boxes at once! Purchase a rank at " . TextFormat::YELLOW . Links::SHOP);
				return;
			}
			$player->showModal(new OpenMultipleUI($player, $box));
		}elseif($response == 3){
			$rh = Structure::RANK_HIERARCHY[$player->getRank()];
			if($rh < 4){
				$player->sendMessage(TextFormat::YELLOW . TextFormat::BOLD . "(i) " . TextFormat::RESET . TextFormat::GRAY . "You need at least " . strtoupper(Core::getInstance()->getChat()->getFormattedRank("enderman")) . " rank to use the Mystery Boxes filter! Purchase a rank at " . TextFormat::YELLOW . Links::SHOP);
				return;
			}

			$player->showModal(new OpenBoxFilterUI($player));
		}
	}

}