<?php namespace prison;

use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;

use prison\Prison;

use core\Core;

class DataBackupTask extends AsyncTask{

	const LOCATION = "/[REDACTED]/backups/";
	public $parameters;
	public $filename;
	public $directory;

	public $data;

	public function __construct(){
		$keys = [
			1005 => "1",
			1006 => "2",
			1007 => "test"
		];

		$name = $keys[Server::getInstance()->getPort()];
		$creds = array_merge(file("/[REDACTED]"), ["prison_" . $name]);
		foreach($creds as $key => $cred) $creds[$key] = str_replace("\n", "", $cred);
		$this->parameters = $creds;

		$this->filename = "prison-" . $name . "_" . date("m-d-y_h:i") . ".sql";
		$directory = $this->directory = self::LOCATION . "prison-" . $name . "/" . date("m-d-y") . "/";
		@mkdir($directory, 0777, true);
	}

	public function onRun() : void{
		$parameters = $this->parameters;
		$filename = $this->filename;
		$directory = $this->directory;

		$db = new \mysqli(...$parameters); 

		$tables = array();
		$result = $db->query("SHOW TABLES");
		while($row = $result->fetch_row()){
			$tables[] = $row[0];
		}

		$return = "";

		foreach($tables as $table){
			$result = $db->query("SELECT * FROM $table");
			$numColumns = $result->field_count;

			$return .= "DROP TABLE $table;";

			$result2 = $db->query("SHOW CREATE TABLE $table");
			$row2 = $result2->fetch_row();

			$return .= "\n\n".$row2[1].";\n\n";

			for($i = 0; $i < $numColumns; $i++){
				while($row = $result->fetch_row()){
					$return .= "INSERT INTO $table VALUES(";
					for($j = 0; $j < $numColumns; $j++){
						$row[$j] = addslashes($row[$j]);
						$row[$j] = str_replace("\n", "\\n", $row[$j]);
						if(isset($row[$j])){
							$return .= '"'.$row[$j].'"';
						}else{
							$return .= '""';
						}
						if($j < ($numColumns-1)){
							$return.= ',';
						}
					}
					$return .= ");\n";
				}
			}

			$return .= "\n\n\n";
		}

		$return = mb_convert_encoding($return, "UTF-8", "auto");

		$handle = fopen($directory . $filename, "w+");
		fwrite($handle, pack("CCC",0xef,0xbb,0xbf)); 
		fwrite($handle, $return);
		fclose($handle);
	}

	public function onCompletion() : void{
		Server::getInstance()->getLogger()->info("Backup saved as to " . $this->directory . $this->filename . "!");
	}

}