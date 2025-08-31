<?php namespace prison\gangs;

use prison\Prison;
use prison\gangs\commands\{
	GangCommand,
	ArenaCommand
};

class Gangs{

	public GangManager $gangManager;

	public function __construct(public Prison $plugin){
		foreach([
			//"DROP TABLE IF EXISTS gang_base_data",
			"CREATE TABLE IF NOT EXISTS gang_base_data(
				id BIGINT(10) NOT NULL UNIQUE,
				leader BIGINT(16) NOT NULL,
				name VARCHAR(24) NOT NULL DEFAULT 'My Gang',
				description VARCHAR(250) NOT NULL DEFAULT 'Pancakes',
				level INT NOT NULL DEFAULT 0,
				trophies INT NOT NULL DEFAULT 0,
				kills INT NOT NULL DEFAULT 0,
				deaths INT NOT NULL DEFAULT 0,
				blocks INT NOT NULL DEFAULT 0,
				bank INT NOT NULL DEFAULT 0,
				created INT NOT NULL DEFAULT 0
			)",
			//"DROP TABLE IF EXISTS gang_battle_data",
			"CREATE TABLE IF NOT EXISTS gang_battle_data(
				id BIGINT(10) NOT NULL UNIQUE,
				kills INT NOT NULL DEFAULT 0,
				deaths INT NOT NULL DEFAULT 0,
				wins INT NOT NULL DEFAULT 0,
				losses INT NOT NULL DEFAULT 0,
				draws INT NOT NULL DEFAULT 0
			)",
			//"DROP TABLE IF EXISTS gang_alliances",
			"CREATE TABLE IF NOT EXISTS gang_alliances(
				gangid BIGINT(10) NOT NULL,
				alliance BIGINT(10) NOT NULL,
				created INT NOT NULL DEFAULT 0,
				PRIMARY KEY (gangid, alliance)
			)",
			//"DROP TABLE IF EXISTS gang_members",
			"CREATE TABLE IF NOT EXISTS gang_members(
				xuid BIGINT(16) NOT NULL UNIQUE,
				gangid BIGINT(10) NOT NULL,
				role INT NOT NULL DEFAULT 0,
				kills INT NOT NULL DEFAULT 0,
				deaths INT NOT NULL DEFAULT 0,
				blocks INT NOT NULL DEFAULT 0,
				joined INT NOT NULL DEFAULT 0
			)",
		] as $query) $plugin->getSessionManager()->getDatabase()->query($query);

		$this->gangManager = new GangManager($this);

		$plugin->getServer()->getCommandMap()->registerAll("gangs", [
			new GangCommand($plugin, "gangs", "Gangs command"),
			new ArenaCommand($plugin, "a", "?"),
		]);
	}

	public function getGangManager() : ?GangManager{
		return $this->gangManager;
	}

	public function tick() : void{
		$this->getGangManager()->tick();
	}

	public function close() : void{
		$this->getGangManager()->close();
	}

}