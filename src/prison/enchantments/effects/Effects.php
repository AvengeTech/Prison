<?php namespace prison\enchantments\effects;

use pocketmine\item\ItemFactory;

use prison\Prison;
use prison\PrisonPlayer;
use prison\enchantments\Enchantments;
use prison\enchantments\effects\commands\{
	AddEffect,
	GiveAnimator
};
use prison\enchantments\effects\items\EffectItem;

class Effects{

	public $plugin;
	public $enchantments;

	public $effects = [];
	public $calls;

	public function __construct(Prison $plugin, Enchantments $enchantments){
		$this->plugin = $plugin;
		$this->enchantments = $enchantments;

		$this->calls = new EffectCalls();
		foreach(EffectIds::EFFECTS as $id => $data){
			$this->effects[$id] = new EffectClass(
				$id,
				$data["name"],
				$data["description"] ?? "A new animation!",
				$data["rarity"],
				$data["type"],
				$data["obtainable"] ?? true
			);
		}

		$plugin->getServer()->getCommandMap()->registerAll("ieffects", [
			new AddEffect($plugin, "addieffect", "Adds a sword/tool animation to held item (T3)"),
			new GiveAnimator($plugin, "giveanimator", "Gives animation item (T3)"),
		]);
	}

	public function getEffects(int $rarity = -1, bool $unobtainable = false) : array{
		if($rarity == -1) return $this->effects;
		$effects = [];
		foreach($this->getEffects() as $id => $effect){
			if($effect->getRarity() == $rarity){
				if($effect->isObtainable()){
					$effects[$id] = clone $effect;
				}else{
					if($unobtainable) $effects[$id] = clone $effect;
				}
			}
		}
		return $effects;
	}

	public function getEffectById(int $id) : ?EffectClass{
		return $this->effects[$id] ?? null;
	}

	public function getEffectByName(string $name, bool $unobtainable = false) : ?EffectClass{
		foreach($this->effects as $effect){
			if(
				strtolower($effect->getName()) == strtolower($name) &&
				($effect->isObtainable() || $unobtainable)
			) return $effect;
		}
		return null;
	}

	public function getRandomEffect(int $rarity = 1, bool $unobtainable = false) : ?EffectClass{
		$effects = $this->effects;
		shuffle($effects);
		foreach($effects as $id => $effect){
			if($effect->getRarity() == $rarity && ($effect->isObtainable() || $unobtainable)){
				return $effect;
			}
		}
		return null;
	}

	public static function fromImage($img){
		$bytes = "";
		for($y = 0; $y < imagesy($img); $y++){
			for($x = 0; $x < imagesx($img); $x++){
				$rgba = @imagecolorat($img, $x, $y);
				$a = ((~(($rgba >> 24))) << 1) & 0xff;
				$r = ($rgba >> 16) & 0xff;
				$g = ($rgba >> 8) & 0xff;
				$b = $rgba & 0xff;
				$bytes .= chr($r) . chr($g) . chr($b) . chr($a);
			}
		}
		@imagedestroy($img);
		return $bytes;
	}

}