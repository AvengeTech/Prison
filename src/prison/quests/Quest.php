<?php

namespace prison\quests;

use core\utils\conversion\LegacyItemIds;
use core\utils\ItemRegistry;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

use prison\Prison;
use prison\PrisonPlayer;

class Quest {

	public $id;

	public $name;
	public $level;
	public $rank;

	public $points;

	public $messages = [
		"requirement" => "",
		"request" => "",
		"incomplete" => "",
		"done" => "",
		"undone" => "",
		"complete" => "",
	];

	public $take = "";

	public $progress = []; //Dynamic quest progress data goes here.

	public $complete = false;
	public $selected = false;

	public function __construct($id, $name, $level, $rank, $messages, $take, $startingprogress = []) {
		$this->id = $id;

		$this->name = $name;
		$this->level = $level;
		$this->rank = $rank;

		$this->points = Structure::getPointsFromLevel($level);

		$this->messages = $messages;
		$this->take = $take;

		$this->progress = $startingprogress;
	}

	public function getId() {
		return $this->id;
	}

	public function getName() {
		return $this->name;
	}

	public function getDifficultyLevel() {
		return $this->level;
	}

	public function getRequiredRank() {
		return $this->rank;
	}

	public function getPoints() {
		return $this->points;
	}

	public function getMessage($key) {
		$message = $this->messages[$key] ?? "Message not found!";
		return $message;
	}

	/** @return Item[] */
	public function getTake(): array {
		return $this->take;
	}

	public function isComplete() {
		return $this->complete;
	}

	public function setComplete($bool, Player $player) {
		$this->complete = $bool;

		if ($bool) {
			$player->sendMessage(TextFormat::DARK_PURPLE . $this->getName() . ": " . TextFormat::GRAY . $this->getMessage("done"));
			$player->sendMessage(TextFormat::GREEN . TextFormat::BOLD . "(!) " . TextFormat::RESET . TextFormat::GRAY . "You can now turn in your quest! To turn it in, please return to the Questmaster at spawn, or type /quest status");
		} else {
			$player->sendMessage(TextFormat::DARK_PURPLE . $this->getName() . ": " . TextFormat::GRAY . $this->getMessage("undone"));
		}
	}

	public function getProgressString() {
		$string = "";
		foreach ($this->progress as $key => $data) {
			if (is_array($data)) {
				if (count($data) == 2) {
					$string .= ucwords($key) . ": " . $data[0] . "/" . $data[1] . PHP_EOL;
				} else {
					$string .= ucwords($key) . ": " . $data[0] . PHP_EOL;
				}
			} else {
				$string .= ucwords($key) . ": " . $data . PHP_EOL;
			}
		}
		return $string;
	}

	public function take(Player $player) {
		/** @var PrisonPlayer $player */
		$take = $this->getTake();
		if (count($take) < 1) return;
		foreach ($take as $t) {
			if ($t instanceof Item) {
				$player->getInventory()->removeItem($t);
			} elseif (is_numeric($t)) {
				$player->takeTechits($t);
			}
		}
	}

	public function turnin(QuestsComponent $session) {
		$this->take($session->getPlayer());

		$session->addCompletedQuest();
		$session->addPoints($this->getPoints());
		$session->setCooldown($this->selected ? QuestsComponent::QUEST_COOLDOWN_ADDITION : 0);

		$session->setActiveQuest();

		unset(Prison::getInstance()->getQuests()->random[$session->getPlayer()->getName()]);
	}
}
