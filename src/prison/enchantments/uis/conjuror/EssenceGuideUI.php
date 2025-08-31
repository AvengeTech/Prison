<?php

namespace prison\enchantments\uis\conjuror;

use core\AtPlayer;
use core\ui\elements\simpleForm\Button;
use core\ui\windows\SimpleForm;
use core\utils\TextFormat as TF;

class EssenceGuideUI extends SimpleForm{

	public function __construct(AtPlayer $atPlayer){
		$content = TF::GOLD . TF::BOLD . "What is Esssence?\n";
		$content .= TF::RESET . TF::WHITE . "Essence is a currency you can use with the " . TF::BLUE . "Conjuror" . TF::WHITE . ". ";
		$content .= TF::RESET . TF::WHITE . "It is used for refining and using Essence Items.\n\n";
		$content .= TF::GOLD . TF::BOLD . "How to earn Esssence?\n";
		$content .= TF::RESET . TF::WHITE . "You can get " . TF::DARK_AQUA . "essence" . TF::WHITE . " by mining blocks or through the quest shop from the " . TF::MINECOIN_GOLD . "Quest Master" . TF::WHITE . ". ";
		$content .= TF::RESET . TF::WHITE . "Every 75 blocks you'll earn between 3-5 " . TF::DARK_AQUA . "essence" . TF::WHITE . ". ";
		$content .= TF::RESET . TF::WHITE . "You can also add the " . TF::YELLOW . "Transmutation" . TF::WHITE . " enchantment to increase the max amount of " . TF::DARK_AQUA . "essence" . TF::WHITE . " you can gain.\n\n";
		$content .= TF::GOLD . TF::BOLD . "What are Essence Items?\n";
		$content .= TF::RESET . TF::WHITE . "Items considered as Essence Items are " . TF::GREEN . "Essence of Success" . TF::WHITE . " & " . TF::AQUA . "Essence of Knowledge" . TF::WHITE . ". ";
		$content .= TF::RESET . TF::WHITE . "There will be more Essence Items to come in future updates!\n\n";
		$content .= TF::GOLD . TF::BOLD . "What is Essence of Success?\n";
		$content .= TF::RESET . TF::GREEN . "Essence of Success" . TF::WHITE . " increasess enchantment book apply chances or enchantment removers rate to give a book back. ";
		$content .= TF::RESET . TF::WHITE . "They can be found through mystery boxes or Vote Drop Party.\n\n";
		$content .= TF::GOLD . TF::BOLD . "What is Essence of Knowledge?\n";
		$content .= TF::RESET . TF::AQUA . "Essence of Knowledge" . TF::WHITE . " are used to combine two of the same books together or reroll a book. ";
		$content .= TF::RESET . TF::WHITE . "They can be found through mystery boxes or Vote Drop Party.\n\n";
		$content .= TF::GOLD . TF::BOLD . "What is Essence of Progress?\n";
		$content .= TF::RESET . TF::DARK_PURPLE . "Essence of Progress" . TF::WHITE . " are used to give a book the chance to skip the tier system. ";
		$content .= TF::RESET . TF::WHITE . "They can be found through mystery boxes, Vote Drop Party or KOTH.\n\n";
		$content .= TF::GOLD . TF::BOLD . "What is Essence of Ascension?\n";
		$content .= TF::RESET . TF::YELLOW . "Essence of Ascension" . TF::WHITE . " are used on enchanted items to level up an enchantment based on the rarity of the essence. ";
		$content .= TF::RESET . TF::WHITE . "They can be found through mystery boxes, Vote Drop Party or KOTH.\n\n";

		parent::__construct("Essence Guide", $content);

		$this->addButton(new Button(TF::RED . "Close"));
	}
}