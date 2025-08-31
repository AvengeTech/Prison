<?php namespace prison\guards;

use pocketmine\math\Vector3;

class PathManager{

	const DIRECTORY = "/[REDACTED]/guard_paths/";

	public $main;

	public $paths = [];

	public function __construct(Guards $main){
		$this->main = $main;
		$this->setupPaths();
	}

	public function getMain() : Guards{
		return $this->main;
	}

	public function setupPaths() : void{
		foreach(PathData::PATHS as $name => $data){
			$loops = $data["loops"];
			$points = [];
			$p = [];
			foreach($data["points"] as $id => $point){
				$points[$id] = (new Vector3(...$point))->add(0.5,0,0.5);
				//$points[$id] = ($pp = new Vector3(...$point))->add(($pp->x > 0 ? 0.5 : -0.5), 0, ($pp->z > 0 ? 0.5 : -0.5));
			}

			$first = $points[0];
			foreach($points as $id => $point){
				$p[$id] = new Point($id, $point, ($points[$id + 1] ?? $first));
			}
			if(!$loops){
				$p = array_pop($p);
			}

			$this->paths[$name] = new Path($name, $p, $loops);
		}

		foreach(array_diff(scandir(self::DIRECTORY), ["..", "."]) as $file){
			$data = json_decode(file_get_contents(self::DIRECTORY . $file), true);

			$loops = $data["loops"];
			$points = [];
			$p = [];
			foreach($data["points"] as $id => $point){
				$points[$id] = (new Vector3(...$point))->add(0.5,0,0.5);
				//$points[$id] = ($pp = new Vector3(...$point))->add(($pp->x > 0 ? 0.5 : -0.5), 0, ($pp->z > 0 ? 0.5 : -0.5));
			}

			$first = $points[0];
			foreach($points as $id => $point){
				$p[$id] = new Point($id, $point, ($points[$id + 1] ?? $first));
			}
			if(!$loops){
				array_pop($p);
			}

			$name = explode(".", $file)[0];

			$this->paths[$name] = new Path($name, $p, $loops);
		}
	}

	public function getPaths() : array{
		return $this->paths;
	}

	public function getPath(string $name) : ?Path{
		$path = $this->paths[$name] ?? null;
		return ($path instanceof Path ? clone $path : null);
	}

	public function getRandomPath() : ?Path{
		return $this->getPath(($ak = array_keys($this->paths))[array_rand($ak)]);
	}

	public function addPath(Path $path) : void{
		$this->paths[$path->getName()] = $path;
	}

}