<?php namespace prison\tags;

use core\utils\TextFormat;

class Structure{

	const TAG_FORMAT = [
		"AdminABOOSE" => TextFormat::RESET . TextFormat::RED . "Admin" . TextFormat::DARK_RED . "ABOOSE",
		"OwnerABOOSE" => TextFormat::RESET . TextFormat::BLUE . "Owner" . TextFormat::DARK_BLUE . "ABOOSE",
		"ABOOSE" => TextFormat::RESET . TextFormat::GOLD . "AB" . TextFormat::WHITE . "OO" . TextFormat::GOLD . "SE",

		"Sn3akSucks" => TextFormat::RESET . TextFormat::RED . "Sn3ak" . TextFormat::DARK_RED . "Sucks",
		"Maloner" => TextFormat::RESET . TextFormat::BLUE . "Malon" . TextFormat::RED . "er",
		"#GoodGuySn3ak" => TextFormat::RESET . TextFormat::RESET . TextFormat::WHITE . "#" . TextFormat::BOLD . TextFormat::DARK_BLUE . "GoodGuy" . TextFormat::DARK_GREEN . "Sn3ak",
		"#BadGuySn3ak" => TextFormat::RESET . TextFormat::RESET . TextFormat::WHITE . "#" . TextFormat::BOLD . TextFormat::DARK_RED . "BadGuy" . TextFormat::GOLD . "Sn3ak",

		"FakeDev" => TextFormat::RESET . TextFormat::DARK_GRAY . "Fake" . TextFormat::GOLD . "Dev",
		"RichBoi" => TextFormat::RESET . TextFormat::YELLOW . "Rich" . TextFormat::WHITE . "Boi",
		"#DAB" => TextFormat::RESET . TextFormat::RESET . TextFormat::WHITE . "#" . TextFormat::BOLD . TextFormat::GOLD . "D" . TextFormat::YELLOW . "A" . TextFormat::RED . "B",
		"Mineman" => TextFormat::RESET . TextFormat::GRAY . "Mine" . TextFormat::GREEN . "man",
		"HoldThisL" => TextFormat::RESET . TextFormat::GOLD . "Hold" . TextFormat::YELLOW . "This" . TextFormat::DARK_RED . "L",
		"RoadToFree" => TextFormat::RESET . TextFormat::GRAY . "Road" . TextFormat::DARK_GRAY . "To" . TextFormat::YELLOW . "Free",
		"Clickbait" => TextFormat::RESET . TextFormat::DARK_RED . "Cli" . TextFormat::WHITE . "ckb" . TextFormat::DARK_RED . "ait",
		"N00b" => TextFormat::RESET . TextFormat::DARK_GRAY . "N" . TextFormat::GRAY . "00" . TextFormat::AQUA . "b",
		"GucciGang" => TextFormat::RESET . TextFormat::DARK_GREEN . "Gu" . TextFormat::RED . "c" . TextFormat::DARK_GREEN . "ci" . TextFormat::DARK_GRAY . "Gang",
		"Savage" => TextFormat::RESET . TextFormat::LIGHT_PURPLE . "Sa" . TextFormat::RED . "va" . TextFormat::LIGHT_PURPLE . "ge",
		"TryHard" => TextFormat::RESET . TextFormat::YELLOW . "Try" . TextFormat::DARK_RED . "Hard",
		"InsertTagHere" => TextFormat::RESET . TextFormat::AQUA . "Insert" . TextFormat::GREEN . "Tag" . TextFormat::AQUA . "Here",
		"Toxic" => TextFormat::RESET . TextFormat::GREEN . "Tox" . TextFormat::DARK_GREEN . "ic",
		"FreeHugs" => TextFormat::RESET . TextFormat::WHITE . "Free" . TextFormat::LIGHT_PURPLE . "Hugs",
		"YEET" => TextFormat::RESET . TextFormat::YELLOW . "YEET",
		"OOF" => TextFormat::RESET . TextFormat::RED . "O" . TextFormat::GOLD . "O" . TextFormat::WHITE . "F",
		"Boneless" => TextFormat::RESET . TextFormat::BLACK . "Bo" . TextFormat::DARK_GRAY . "ne" . TextFormat::GRAY . "le" . TextFormat::WHITE . "ss",
		"OnTheGrind" => TextFormat::RESET . TextFormat::GRAY . "On" . TextFormat::DARK_GRAY . "The" . TextFormat::GOLD . "Grind",

		"Melon" => TextFormat::RESET . TextFormat::RED . "M" . TextFormat::GREEN . "elo" . TextFormat::RED . "n",
		"HoneyMustard" => TextFormat::RESET . TextFormat::GOLD . "Honey" . TextFormat::YELLOW . "Mustard",
		"#Chocaholic" => TextFormat::RESET . TextFormat::RESET . TextFormat::WHITE . "#" . TextFormat::BOLD . TextFormat::DARK_GRAY . "Cho" . TextFormat::GOLD . "caho" . TextFormat::DARK_GRAY . "lic",

		"TechieIsBae" => TextFormat::RESET . TextFormat::AQUA . "Tec" . TextFormat::GOLD . "hie" . TextFormat::WHITE . "Is" . TextFormat::RED . "Bae",
		"Sn3akIsBae" => TextFormat::RESET . TextFormat::DARK_AQUA . "Sn3ak" . TextFormat::BLUE . "Is" . TextFormat::DARK_BLUE . "Bae",
		"ForeverAlone" => TextFormat::RESET . TextFormat::WHITE . "For" . TextFormat::GRAY . "ever" . TextFormat::DARK_GRAY . "Al" . TextFormat::BLACK . "one",
		"Loner" => TextFormat::RESET . TextFormat::GRAY . "Loner",
		"123forGF" => TextFormat::RESET . TextFormat::WHITE . "123" . TextFormat::GRAY . "For" . TextFormat::LIGHT_PURPLE . "GF",
		"123forBF" => TextFormat::RESET . TextFormat::WHITE . "123" . TextFormat::GRAY . "For" . TextFormat::AQUA . "BF",

		"#ATFTW" => TextFormat::RESET . TextFormat::RESET . TextFormat::WHITE . "#" . TextFormat::BOLD . TextFormat::AQUA . "A" . TextFormat::GOLD . "T" . TextFormat::GREEN . "FTW",
		"AvengeTechJuice" => TextFormat::RESET . TextFormat::AQUA . "Avenge" . TextFormat::GOLD . "Tech" . TextFormat::DARK_BLUE . "Juice",
		"ATisMyCity" => TextFormat::RESET . TextFormat::AQUA . "A" . TextFormat::GOLD . "T" . TextFormat::YELLOW . "is" . TextFormat::AQUA . "My" . TextFormat::GREEN . "City",
		"ATisBad" => TextFormat::RESET . TextFormat::AQUA . "A" . TextFormat::GOLD . "T" . TextFormat::YELLOW . "is" . TextFormat::DARK_RED . "Bad",

		"BeepBeepLettuce" => TextFormat::RESET . TextFormat::AQUA . "Beep" . TextFormat::GOLD . "Beep" . TextFormat::GREEN . "Lettuce",
		"AmANGERY" => TextFormat::RESET . TextFormat::BLACK . "Am" . TextFormat::DARK_RED . "ANG" . TextFormat::GOLD . "ERY",
		"Memz" => TextFormat::RESET . TextFormat::GREEN . "Mem" . TextFormat::DARK_GREEN . "z",
		"Nani" => TextFormat::RESET . TextFormat::DARK_RED . "Nani",
		"MemeKing" => TextFormat::RESET . TextFormat::GREEN . "Meme" . TextFormat::BLUE . "King",
		"MemeQueen" => TextFormat::RESET . TextFormat::GREEN . "Meme" . TextFormat::RED . "Queen",
		"MemeGod" => TextFormat::RESET . TextFormat::GREEN . "Meme" . TextFormat::YELLOW . "God",
		"#CloutGang" => TextFormat::RESET . TextFormat::RESET . TextFormat::WHITE . "#" . TextFormat::BOLD . "Clout" . TextFormat::GRAY . "Gang",

		"WOAH" => TextFormat::RESET . TextFormat::GREEN . TextFormat::OBFUSCATED . "!" . TextFormat::RESET . TextFormat::BOLD . TextFormat::GOLD . "WOAH" . TextFormat::GREEN . TextFormat::OBFUSCATED . "!" . TextFormat::RESET,
		"RUN" => TextFormat::RESET . TextFormat::DARK_RED . "R" . TextFormat::GOLD . "U" . TextFormat::DARK_RED . "N",
		"#JustDoIt" => TextFormat::RESET . TextFormat::RESET . TextFormat::WHITE . "#" . TextFormat::BOLD . TextFormat::RED . "Just" . TextFormat::DARK_RED . "Do" . TextFormat::RED . "It",

		"OwO" => TextFormat::RESET . TextFormat::GOLD . "O" . TextFormat::YELLOW . "w" . TextFormat::GOLD . "O",
		"UwU" => TextFormat::RESET . TextFormat::GOLD . "U" . TextFormat::YELLOW . "w" . TextFormat::GOLD . "U",
		"MaloneChan" => TextFormat::RESET . TextFormat::BLUE . "Malone" . TextFormat::GOLD . "Chan",
		"TechieChan" => TextFormat::RESET . TextFormat::AQUA . "Techie" . TextFormat::GOLD . "Chan",
		"Weeb" => TextFormat::RESET . TextFormat::WHITE . TextFormat::OBFUSCATED . "!" . TextFormat::RESET . TextFormat::BOLD . TextFormat::LIGHT_PURPLE . "W" . TextFormat::AQUA . "e" . TextFormat::YELLOW . "e" . TextFormat::GREEN . "b" . TextFormat::OBFUSCATED . TextFormat::WHITE . "!" . TextFormat::RESET,
		"DaWae" => TextFormat::RESET . TextFormat::DARK_RED . "Da" . TextFormat::RED . "Wae",
		"MYSPAGHET" => TextFormat::RESET . TextFormat::DARK_GRAY . "MY" . TextFormat::GOLD . "SPAGHET",
		"RawrXD" => TextFormat::RESET . TextFormat::GREEN . "Rawr" . TextFormat::GRAY . "XD",
		"Ezpz" => TextFormat::RESET . TextFormat::GREEN . "Ez" . TextFormat::AQUA . "pz",
		"Woof" => TextFormat::RESET . TextFormat::BLUE . "W" . TextFormat::RED . "oo" . TextFormat::BLUE . "f",
		"Meow" => TextFormat::RESET . TextFormat::RED . "Meow",
		"Oink" => TextFormat::RESET . TextFormat::LIGHT_PURPLE . "Oink",
		"Mooooo" => TextFormat::RESET . TextFormat::BLACK . "M" . TextFormat::WHITE . "o" . TextFormat::BLACK . "o" . TextFormat::WHITE . "o" . TextFormat::BLACK . "o" . TextFormat::WHITE . "o",

		"Useless" => TextFormat::RESET . TextFormat::GOLD . "Use" . TextFormat::DARK_RED . "less",
		"Potato" => TextFormat::RESET . TextFormat::GOLD . "Potato",
		"Spoon" => TextFormat::RESET . TextFormat::GRAY . "Spoon",
		"Denied" => TextFormat::RESET . TextFormat::DARK_RED . "Denied",
		"Kek" => TextFormat::RESET . TextFormat::GOLD . "K" . TextFormat::YELLOW . "e" . TextFormat::GOLD . "k",
		"LOL" => TextFormat::RESET . TextFormat::YELLOW . "L" . TextFormat::WHITE . "O" . TextFormat::YELLOW . "L",
		"Chugger" => TextFormat::RESET . TextFormat::YELLOW . "Chug" . TextFormat::GOLD . "ger",
		"Baguette" => TextFormat::RESET . TextFormat::GOLD . "Baguette",
		"IceJuice" => TextFormat::RESET . TextFormat::AQUA . "Ice" . TextFormat::WHITE . "Juice",
		"Zucc" => TextFormat::RESET . TextFormat::AQUA . TextFormat::OBFUSCATED . "!" . TextFormat::RESET . TextFormat::BOLD . TextFormat::AQUA . "Zucc" . TextFormat::OBFUSCATED . "!" . TextFormat::RESET,
		"FeelsBadMan" => TextFormat::RESET . TextFormat::GREEN . "Feels" . TextFormat::DARK_RED . "Bad" . TextFormat::DARK_GREEN . "Man",
		"#NoLife" => TextFormat::RESET . TextFormat::RESET . TextFormat::WHITE . "#" . TextFormat::BOLD . TextFormat::RED . "No" . TextFormat::YELLOW . "Life",
		"KingOfAT" => TextFormat::RESET . TextFormat::OBFUSCATED . TextFormat::BLUE . ";" . TextFormat::AQUA . ";" . TextFormat::RESET . TextFormat::BOLD . TextFormat::YELLOW . "King" . TextFormat::WHITE . "Of" . TextFormat::AQUA . "A" . TextFormat::GOLD . "T" . TextFormat::OBFUSCATED . TextFormat::AQUA . ";" . TextFormat::BLUE . ";" . TextFormat::RESET,
		"QueenOfAT" => TextFormat::RESET . TextFormat::OBFUSCATED . TextFormat::DARK_PURPLE . ";" . TextFormat::LIGHT_PURPLE . ";" . TextFormat::RESET . TextFormat::BOLD . TextFormat::YELLOW . "Queen" . TextFormat::WHITE . "Of" . TextFormat::AQUA . "A" . TextFormat::GOLD . "T" . TextFormat::OBFUSCATED . TextFormat::LIGHT_PURPLE . ";" . TextFormat::DARK_PURPLE . ";" . TextFormat::RESET,

		"L" => TextFormat::RESET . TextFormat::ITALIC . TextFormat::GOLD . "L",
		"AMOGUS" => TextFormat::RESET . TextFormat::OBFUSCATED . TextFormat::WHITE . "ii" . TextFormat::RESET . TextFormat::BOLD . TextFormat::RED . "AMOGUS!" . TextFormat::OBFUSCATED . TextFormat::WHITE . "ii" . TextFormat::RESET,
		"SussyImposter" => TextFormat::RESET . TextFormat::RED . "Sussy" . TextFormat::AQUA . "Impostor",
		"Grape" => TextFormat::RESET . TextFormat::DARK_PURPLE . "Grape",
		"Bean" => TextFormat::RESET . TextFormat::YELLOW . "Bean",
		"Cheese" => TextFormat::RESET . TextFormat::GOLD . "Cheese",
		"Salad" => TextFormat::RESET . TextFormat::GREEN . "Salad",
		"PogChamp" => TextFormat::RESET . TextFormat::YELLOW . "Pog" . TextFormat::GRAY . "Champ",
		"WeirdChamp" => TextFormat::RESET . TextFormat::DARK_RED . "Weird" . TextFormat::GRAY . "Champ",
		"P2W" => TextFormat::RESET . TextFormat::BLUE . "P" . TextFormat::DARK_AQUA . "2" . TextFormat::AQUA . "W",
		"FixTPS" => TextFormat::RESET . TextFormat::DARK_GREEN . "Fix" . TextFormat::GREEN . "TPS",
		"BruhMoment" => TextFormat::RESET . TextFormat::DARK_PURPLE . "Bruh" . TextFormat::LIGHT_PURPLE . "Moment",
		"Spicy" => TextFormat::RESET . TextFormat::RED . "S" . TextFormat::DARK_RED . "p" . TextFormat::RED . "i" . TextFormat::DARK_RED . "c" . TextFormat::RED . "y",
		"Brazil" => TextFormat::RESET . TextFormat::DARK_GREEN . "Bra" . TextFormat::YELLOW . "zil",
		"USA" => TextFormat::RESET . TextFormat::RED . "U" . TextFormat::WHITE . "S" . TextFormat::BLUE . "A",
		"Japan" => TextFormat::RESET . TextFormat::WHITE . "Ja" . TextFormat::DARK_RED . "p" . TextFormat::WHITE . "an",
		"UK" => TextFormat::RESET . TextFormat::BLUE . "U" . TextFormat::DARK_RED . "K",
		"Scotland" => TextFormat::RESET . TextFormat::DARK_AQUA . "Scot" . TextFormat::WHITE . "land",
		"MineResetter" => TextFormat::RESET . TextFormat::GRAY . "Mine" . TextFormat::WHITE . "Resetter",
		"Grass" => TextFormat::RESET . TextFormat::DARK_GREEN . "Grass",
		"Fortnite" => TextFormat::RESET . TextFormat::BLUE . "Fortnite",
		"iForgor" => TextFormat::RESET . TextFormat::AQUA . "i" . TextFormat::DARK_GRAY . "Forgor",
		"iRember" => TextFormat::RESET . TextFormat::AQUA . "i" . TextFormat::YELLOW . "Rember",
		"DidIAsk" => TextFormat::RESET . TextFormat::DARK_PURPLE . "Did" . TextFormat::LIGHT_PURPLE . "IAsk",
		"SHEEESH" => TextFormat::RESET . TextFormat::OBFUSCATED . TextFormat::WHITE . "i" . TextFormat::RESET . TextFormat::BOLD . TextFormat::DARK_AQUA . "SHEEESH" . TextFormat::OBFUSCATED . TextFormat::WHITE . "i" . TextFormat::RESET,
		//"DaBaby" => TextFormat::RESET . TextFormat::DARK_RED . "Da" . TextFormat::RED . "Baby",
		"Crazy" => TextFormat::RESET . TextFormat::RED . "C" . TextFormat::BOLD . TextFormat::OBFUSCATED . TextFormat::WHITE . "ii" . TextFormat::RESET . TextFormat::BOLD . TextFormat::GOLD . "r" . TextFormat::BOLD . TextFormat::OBFUSCATED . TextFormat::WHITE . "ii" . TextFormat::RESET . TextFormat::YELLOW . TextFormat::BOLD . "a" . TextFormat::OBFUSCATED . TextFormat::WHITE . "ii" . TextFormat::RESET . TextFormat::BOLD . TextFormat::GREEN . "z" . TextFormat::OBFUSCATED . TextFormat::WHITE . TextFormat::OBFUSCATED . TextFormat::WHITE . "ii" . TextFormat::RESET . TextFormat::BOLD . TextFormat::AQUA . "y",
		"MissTheRage???" => TextFormat::RESET . TextFormat::DARK_AQUA . "Miss" . TextFormat::BLUE . "The" . TextFormat::AQUA . "Rage" . TextFormat::GOLD . "???",
		"FEELTHERAGE" => TextFormat::RESET . TextFormat::YELLOW . "FEEL" . TextFormat::GOLD . "THE" . TextFormat::RED . "RAGE",
		"Cringe" => TextFormat::RESET . TextFormat::BLACK . "Cringe",
		"#MadCuzBad" => TextFormat::RESET . TextFormat::WHITE . "#" . TextFormat::GOLD . "Mad" . TextFormat::LIGHT_PURPLE . "Cuz" . TextFormat::RED . "Bad",
		"OkBoomer" => TextFormat::RESET . TextFormat::GRAY . "Ok" . TextFormat::DARK_PURPLE . "Boomer",

		//"P" => TextFormat::RESET . TextFormat::BOLD . TextFormat::BLUE . "P",

		//NEW NEW
		"o7" => TextFormat::RESET . TextFormat::BOLD . TextFormat::YELLOW . "o7", //rip techno
		"crayon" => TextFormat::RESET . TextFormat::BOLD . TextFormat::RED . "C" . TextFormat::GOLD . "R" . TextFormat::YELLOW . "A" . TextFormat::GREEN . "Y" . TextFormat::BLUE . "O" . TextFormat::LIGHT_PURPLE . "N",
		"fard" => TextFormat::RESET . TextFormat::BOLD . TextFormat::DARK_RED . "FARD",

		//Christmas
		"Santa" => TextFormat::RESET . TextFormat::WHITE . "San" . TextFormat::DARK_RED . "ta",
		"Dasher" => TextFormat::RESET . TextFormat::DARK_GREEN . "Das" . TextFormat::GREEN . "her",
		"Dancer" => TextFormat::RESET . TextFormat::YELLOW . "Dan" . TextFormat::RED . "cer",
		"Prancer" => TextFormat::RESET . TextFormat::GRAY . "Pran" . TextFormat::WHITE . "cer",
		"Vixen" => TextFormat::RESET . TextFormat::DARK_GRAY . "Vi" . TextFormat::GRAY . "xen",
		"Comet" => TextFormat::RESET . TextFormat::DARK_PURPLE . "Co" . TextFormat::LIGHT_PURPLE . "met",
		"Cupid" => TextFormat::RESET . TextFormat::GOLD . "Cup" . TextFormat::GRAY . "id",
		"Donner" => TextFormat::RESET . TextFormat::DARK_AQUA . "Don" . TextFormat::AQUA . "ner",
		"Blixen" => TextFormat::RESET . TextFormat::AQUA . "Bli" . TextFormat::BLUE . "xen",
		"Rudolph" => TextFormat::RESET . TextFormat::RED . "Rud" . TextFormat::DARK_RED . "olph",

		//Valentines
		"Lovebird" => TextFormat::RESET . TextFormat::DARK_RED . "Love" . TextFormat::YELLOW . "bird",
		"ILY" => TextFormat::RESET . TextFormat::DARK_PURPLE . "I" . TextFormat::RED . "L" . TextFormat::LIGHT_PURPLE . "Y",
		"Flirty" => TextFormat::RESET . TextFormat::LIGHT_PURPLE . "Fli" . TextFormat::RED . "rty",
		"Valentine" => TextFormat::RESET . TextFormat::DARK_PURPLE . "Val" . TextFormat::LIGHT_PURPLE . "ent" . TextFormat::RED . "ine",
		"Admirer" => TextFormat::RESET . TextFormat::RED . "Admir" . TextFormat::DARK_RED . "er",
		"BeMine" => TextFormat::RESET . TextFormat::RED . "Be" . TextFormat::LIGHT_PURPLE . "Mine",

		//Halloween
		"Spooky" => TextFormat::RESET . TextFormat::GOLD . "Sp" . TextFormat::BLACK . "oo" . TextFormat::GOLD . "ky",
		"Jack-o-Lantern" => TextFormat::RESET . TextFormat::GOLD . "Jack" . TextFormat::MINECOIN_GOLD . "-" . TextFormat::YELLOW . "o" . TextFormat::MINECOIN_GOLD . "-" . TextFormat::GOLD . "Lantern",
		"ShanesForehead" => TextFormat::RESET . TextFormat::RED . "Shane's" . TextFormat::BOLD . TextFormat::DARK_RED . "FOREHEAD",
		"Boo" => TextFormat::RESET . TextFormat::BOLD . TextFormat::BLACK . "B" . TextFormat::DARK_GRAY . "O" . TextFormat::BLACK . "O",
		"Halloween" => TextFormat::RESET . TextFormat::GOLD . "H" . TextFormat::BLACK . "a" . TextFormat::GOLD . "l" . TextFormat::BLACK . "l" . TextFormat::GOLD . "o" . TextFormat::BLACK . "w" . TextFormat::GOLD . "e" . TextFormat::BLACK . "e" . TextFormat::GOLD . "n",
		"MonsterMash" => TextFormat::RESET . TextFormat::DARK_GRAY . "Monster" . TextFormat::DARK_GREEN . "Mash",
		"SpookyScary" => TextFormat::RESET . TextFormat::BLACK . "Spooky" . TextFormat::GRAY . "Scary",
		"Witch" => TextFormat::RESET . TextFormat::DARK_PURPLE . "Witch",
		"Tombstone" => TextFormat::RESET . TextFormat::DARK_GRAY . "Tomb" . TextFormat::GRAY . "stone",
		"Ghoul" => TextFormat::RESET . TextFormat::BOLD . TextFormat::WHITE . "Ghoul",
		"HashSlingingSlasher" => TextFormat::RESET . TextFormat::BLACK . "Hash" . TextFormat::AQUA . "Slinging" . TextFormat::RED . "Slasher",

		//Other
		"#iVoted" => TextFormat::RESET . TextFormat::WHITE . "#" . TextFormat::BOLD . "i" . TextFormat::YELLOW . "Voted",

		//Dev
		"CaliKid" => TextFormat::RESET . TextFormat::EMOJI_SUN . TextFormat::GOLD . "Cali" . TextFormat::MINECOIN_GOLD . "Kid" . TextFormat::EMOJI_SUN,
		"Reformed>AT" => TextFormat::RESET . TextFormat::BOLD . TextFormat::GOLD . 'Refo' . TextFormat::BLACK . 'rmed' . TextFormat::WHITE . TextFormat::RESET . '>' . TextFormat::BOLD . TextFormat::AQUA . 'A' . TextFormat::GOLD . 'T',
		"SquishIsHot" => TextFormat::RESET . TextFormat::LIGHT_PURPLE . "Squish" . TextFormat::RED . "Is" . TextFormat::LIGHT_PURPLE . "Hot",
		"Ploogerrag" => TextFormat::RESET . TextFormat::GRAY . "Ploogerrag",
		"RexamusRex" => TextFormat::RESET . TextFormat::BLUE . 'RexmusRex'
	];

	const DISABLED_TAGS = [
		//Christmas
		"Santa",
		"Dasher",
		"Dancer",
		"Prancer",
		"Vixen",
		"Comet",
		"Cupid",
		"Dunder",
		"Blixen",
		"Rudolph",

		//Valentines
		/*
		"Lovebird",
		"ILY",
		"Flirty",
		"Valentine",
		"Admirer",
		"BeMine",
		*/

		//Halloween
		"Spooky",
		"Jack-o-Lantern",
		"ShanesForehead",
		"Boo",
		"Halloween",
		"MonsterMash",
		"SpookyScary",
		"Witch",
		"Tombstone",
		"Ghoul",
		"HashSlingingSlasher",

		//Other
		"#iVoted",

		//Dev
		"CaliKid",
		"Reformed>AT",
		"Ploogerrag",
		"SquishIsHot",
		"RexamusRex"
	];

}
