<?php namespace prison\tags;

use prison\Prison;
use prison\tags\commands\{
	Tags as TagCmd,
	AddTag,
	AddRandomTags
};

class Tags{

	public array $tags = [];

	public function __construct(public Prison $plugin){
		$this->setupTags();

		$this->plugin->getServer()->getCommandMap()->registerAll("tags", [
			new TagCmd($plugin, "tags", "Open tag menu"),
			new AddTag($plugin, "addtag", "Give people tags"),
			new AddRandomTags($plugin, "addrandomtags", "Give people random tags"),
		]);
	}

	public function setupTags() : void{
		foreach(Structure::TAG_FORMAT as $tag => $format){
			$this->tags[$tag] = new Tag($tag, $format, in_array($tag, Structure::DISABLED_TAGS));
		}
	}

	public function getTags(bool $disabled = true) : array{
		if($disabled) return $this->tags;
		$tags = [];
		foreach($this->getTags() as $tag){
			if(!$tag->isDisabled()) $tags[$tag->getName()] = $tag;
		}
		return $tags;
	}

	public function getTag(string $name) : ?Tag{
		if(isset($this->tags[$name])){
			return clone $this->tags[$name];
		}
		return null;
	}

	public function getRandomTag(?array $tags = null) : ?Tag{
		$tags = $tags ?? $this->getTags();
		$t = [];
		$key = 0;
		foreach($tags as $tag){
			$t[$key] = $tag;
			$key++;
		}
		$tag = $t[mt_rand(0, $key - 1)];
		if($tag->isDisabled())
			return $this->getRandomTag($tags);

		return $tag;
	}

}