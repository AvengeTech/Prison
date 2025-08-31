<?php namespace prison\kits;

use pocketmine\player\Player;

use prison\kits\commands\KitCommand;
use prison\Prison;
use prison\PrisonPlayer;

use core\Core;
use core\utils\{
	ItemRegistry,
	TextFormat,
	conversion\LegacyItemIds
};
use pocketmine\item\VanillaItems;

class Kits{

	public array $kits = [];

	public function __construct(public Prison $plugin){
		$this->kits = [
			"starter" => new Kit("starter",
				[
					VanillaItems::IRON_PICKAXE()->setCustomName("Starter Pickaxe"),
					VanillaItems::IRON_AXE()->setCustomName("Starter Axe"),
					VanillaItems::STEAK()->setCount(4),
				],
				[], 1, "default"
			),
			"weekly" => new Kit("weekly", [
				VanillaItems::IRON_SWORD(),
				VanillaItems::IRON_PICKAXE()->setCount(2),
				VanillaItems::IRON_AXE(),
				VanillaItems::COOKED_CHICKEN()->setCount(16),
			], [
				0 => VanillaItems::LEATHER_CAP(),
				1 => VanillaItems::LEATHER_TUNIC(),
				2 => VanillaItems::LEATHER_PANTS(),
				3 => VanillaItems::LEATHER_BOOTS(),
			], 24 * 7, "default"),
			"monthly" => new Kit("monthly", [
				VanillaItems::DIAMOND_SWORD(),
				VanillaItems::DIAMOND_PICKAXE(),
				VanillaItems::DIAMOND_AXE(),
				VanillaItems::STEAK()->setCount(16),
			], [
				0 => VanillaItems::IRON_HELMET(),
				1 => VanillaItems::IRON_CHESTPLATE(),
				2 => VanillaItems::IRON_LEGGINGS(),
				3 => VanillaItems::IRON_BOOTS(),
			], 24 * 31, "default"),
		];

		if(Core::getInstance()->getNetwork()->getServerManager()->getThisServer()->getTypeId() !== "event"){
			$this->kits["blaze"] = new Kit("blaze", [
				VanillaItems::IRON_SWORD(),
				VanillaItems::IRON_PICKAXE()->setCount(2),
				VanillaItems::GOLDEN_PICKAXE()->setCount(2),
				VanillaItems::GOLDEN_AXE(),
				VanillaItems::COOKED_PORKCHOP()->setCount(16),
				VanillaItems::COOKED_CHICKEN()->setCount(8),
				VanillaItems::GOLDEN_APPLE()->setCount(2),
			], [
				0 => VanillaItems::GOLDEN_HELMET(),
				1 => VanillaItems::GOLDEN_CHESTPLATE(),
				2 => VanillaItems::GOLDEN_LEGGINGS(),
				3 => VanillaItems::GOLDEN_BOOTS(),
			], 24, "blaze");

			$this->kits["ghast"] = new Kit("ghast", [
				VanillaItems::IRON_SWORD(),
				VanillaItems::IRON_PICKAXE(),
				VanillaItems::IRON_AXE(),
				VanillaItems::STEAK()->setCount(16),
				VanillaItems::COOKED_PORKCHOP()->setCount(16),
				VanillaItems::GOLDEN_APPLE()->setCount(3),
			], [
				0 => VanillaItems::CHAINMAIL_HELMET(),
				1 => VanillaItems::CHAINMAIL_CHESTPLATE(),
				2 => VanillaItems::CHAINMAIL_LEGGINGS(),
				3 => VanillaItems::CHAINMAIL_BOOTS(),
			], 24, "ghast");
			$this->kits["enderman"] = new Kit("enderman", [
				VanillaItems::DIAMOND_AXE(),
				VanillaItems::IRON_PICKAXE()->setCount(3),
				VanillaItems::IRON_PICKAXE(),
				VanillaItems::STEAK()->setCount(32),
				VanillaItems::GOLDEN_APPLE()->setCount(4),
			], [
				0 => VanillaItems::IRON_HELMET(),
				1 => VanillaItems::IRON_CHESTPLATE(),
				2 => VanillaItems::IRON_LEGGINGS(),
				3 => VanillaItems::IRON_BOOTS(),
			], 24, "enderman");
			$this->kits["wither"] = new Kit("wither", [
				VanillaItems::DIAMOND_SWORD(),
				VanillaItems::DIAMOND_PICKAXE(),
				VanillaItems::DIAMOND_AXE(),
				VanillaItems::STEAK()->setCount(32),
				VanillaItems::COOKED_PORKCHOP()->setCount(16),
				VanillaItems::GOLDEN_APPLE()->setCount(6),
			], [
				0 => VanillaItems::IRON_HELMET(),
				1 => VanillaItems::DIAMOND_CHESTPLATE(),
				2 => VanillaItems::IRON_LEGGINGS(),
				3 => VanillaItems::DIAMOND_BOOTS(),
			], 24, "wither");
			$this->kits["enderdragon"] = new Kit("enderdragon", [
				VanillaItems::DIAMOND_SWORD(),
				VanillaItems::DIAMOND_SHOVEL(),
				VanillaItems::DIAMOND_PICKAXE(),
				VanillaItems::DIAMOND_AXE(),
				VanillaItems::DIAMOND_HOE(),
				VanillaItems::STEAK()->setCount(64),
				VanillaItems::COOKED_PORKCHOP()->setCount(64),
				VanillaItems::GOLDEN_APPLE()->setCount(8),
			], [
				0 => VanillaItems::DIAMOND_HELMET(),
				1 => VanillaItems::DIAMOND_CHESTPLATE(),
				2 => VanillaItems::DIAMOND_LEGGINGS(),
				3 => VanillaItems::DIAMOND_BOOTS(),
			], 72, "enderdragon");
		}

		$plugin->getServer()->getCommandMap()->register("kit", new KitCommand("kit", "Equip a kit!"));
	}

	public function getKits() : array{
		return $this->kits;
	}

	public function getKitListString(Player $player) : string{
		/** @var PrisonPlayer $player */
		$string = "";
		$session = $player->getGameSession()->getKits();
		foreach($this->getKits() as $kit){
			$string .= TextFormat::GRAY . "- ";
			if($session->hasCooldown($kit->getName()) || !$kit->hasRequiredRank($player)){
				$string .= TextFormat::RED;
			}else{
				$string .= TextFormat::GREEN;
			}
			$string .= $kit->getName() . PHP_EOL;
		}
		return rtrim($string);
	}

	public function getKit(string $name) : ?Kit{
		return $this->kits[$name] ?? null;
	}

	public function kitExists(string $name) : bool{
		return $this->getKit($name) !== null;
	}

}