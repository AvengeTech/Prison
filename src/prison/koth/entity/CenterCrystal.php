<?php namespace prison\koth\entity;

use pocketmine\entity\Entity;
use pocketmine\entity\EntitySizeInfo;
use pocketmine\entity\Location;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataCollection;
use pocketmine\utils\TextFormat;
use pocketmine\player\Player;
use pocketmine\event\entity\EntityDamageEvent;
use prison\enchantments\Calls;
use prison\koth\Game;
use prison\Prison;

class CenterCrystal extends Entity{
	
	public $width = 1;
	public $height = 1;

	public $game;

	public $lastName = "";

	protected function getInitialDragMultiplier(): float
	{
		return 0;
	}

	protected function getInitialGravity(): float
	{
		return 0;
	}

	public function __construct(Location $loc, Game $game = null){
		parent::__construct($loc);
		if($game === null){
			$this->flagForDespawn();
			return;
		}
		$this->game = $game;

		$this->setNametagVisible(true);
		$this->getNetworkProperties()->setByte(81, 1);
	}

	public function getName() : string{
		return "Center Crystal";
	}

	public function getGame() : Game{
		return $this->game;
	}

	public function entityBaseTick(int $tickDiff = 1) : bool{
		parent::entityBaseTick($tickDiff);

		$game = $this->getGame();
		if($game !== null && $game->isActive()){
			$queue = $game->getClaimQueue();
			$first = $queue->getFirstPlayer();
			if($first === null){
				$this->setNametag(TextFormat::YELLOW . "No one claiming.");
			}else{
				$player = $first->getPlayer();
				if($player instanceof Player){
					$this->setNametag(TextFormat::RED . $player->getName() . ": " . TextFormat::YELLOW . gmdate("i:s", (time() - ($first->time - 300))) . TextFormat::GRAY . "/" . TextFormat::GREEN . "05:00");
					if($player->getName() !== $this->lastName){
						$this->lastName = $player->getName();
						Calls::getInstance()->strikeLightning($this->getPosition(), $this);
					}
				}else{
					$this->setNametag(TextFormat::YELLOW . "No one claiming.");
				}
			}
		}

		return $this->isAlive();
	}

	public function canSaveWithChunk() : bool{
		return false;
	}

	public function attack(EntityDamageEvent $source) : void{
		$source->cancel();
	}

	protected function getInitialSizeInfo(): EntitySizeInfo{
		return new EntitySizeInfo(1, 1);
	}

	public static function getNetworkTypeId(): string{
		return EntityIds::ENDER_CRYSTAL;
	}

	protected function syncNetworkData(EntityMetadataCollection $properties): void{
		parent::syncNetworkData($properties);
		$properties->setByte(81, 1);
	}
}