<?
	$CONST = (object)[
		'editionSize' => 10,
		'rootPath' => str_replace(array("\\"), "/", __DIR__),
		'dnaDelimiter' => '-',
		'rarityDelimiter' => '#'
	];

	$LAYERS = [
		"Background",
		"Eyeball",
		"Eye color",
		"Iris",
		"Shine",
		"Bottom lid",
		"Top lid",
	];

	$DNA_LIST = [];

	$IMG_W = 512;
	$IMG_H = 512;
	$IMG_BASE = imagecreatetruecolor($IMG_W, $IMG_H);

	function array_find($xs, $f) {
		foreach ($xs as $x) {
			if ($f($x) === true)
				return $x;
		}
		return null;
	}

	function isDnaUnique($dna) {
		global $DNA_LIST;
		return !in_array($dna, $DNA_LIST);
	}

	function cleanName($name) {
		global $CONST;
		$prettyName = substr($name, 0, -4);
  		$prettyName = explode($CONST->rarityDelimiter, $prettyName)[0];
		return $prettyName;
	}

	function getRarityWeight($picname) {
		global $CONST;
		$nameWithoutExt = substr($picname, 0, -4);
		$rarity = explode($CONST->rarityDelimiter, $nameWithoutExt)[1];
		$rarity = is_null($rarity) ? 1 : $rarity;
		return $rarity;
	}

	function getLayerPictures($layers_dir_path) {
		$unfiltered = scandir($layers_dir_path, SCANDIR_SORT_NONE);
		$images = [];
		foreach ($unfiltered as $k => $v) {
			if (substr($v, -4) === ".png") {
				array_push($images, $v);
			}
		}
		$res = array_map(function($pic, $i) use ($layers_dir_path) {
			return [
				'id' => $i,
				'pic_name' => cleanName($pic),
				'filename' => $pic,
				'path' => "$layers_dir_path/$pic",
				'weight' => getRarityWeight($pic),
			];
		}, $images, array_keys($images));
		// print_r($res);
		return $res;
	}

	function setupLayers() {
		global $LAYERS, $CONST;
		$layers = array_map(function($l, $i) use ($CONST) {
			return [
				"order" => $i,
				"pictures" => getLayerPictures("$CONST->rootPath/layers/$l"),
				"name" => $l,
			];
		}, $LAYERS, array_keys($LAYERS));
		// print_r($layers);
		return $layers;
	}

	function generateDna($layers) {
		global $CONST;
		$dnaNodes = [];
		foreach ($layers as $k => $v) {
			$totalWeight = 0;
			$layer_pics = $v['pictures'];

			// summarize weights of all pictures of a layers
			foreach ($layer_pics as $k => $pic) {
				$totalWeight += (int)$pic['weight'];
			}

			$accumulator = rand(0, $totalWeight);
			for ($i=0; $i < count($layer_pics); $i++) {
				$accumulator -= $layer_pics[$i]['weight'];
				if ($accumulator <= 0) {
					$pic = $layer_pics[$i];
					$pic_id = $pic['id'];
					$pic_filename = $pic['filename'];
					array_push($dnaNodes, "$pic_id:$pic_filename");
					break;
				}
			}
		}
		$dna = implode($CONST->dnaDelimiter, $dnaNodes);
		// echo $dna."<br>";
		return $dna;
	}

	function getDnaNodeId($dna_node) {
		return explode(':', $dna_node)[0];
	}

	function getDnaLayers($dna, $layers) {
		global $CONST;
		$mapping = array_map(function($l, $i) use ($CONST, $dna) {
			$pics = $l['pictures'];
			// get dna node associated with layer
			$dna_node = explode($CONST->dnaDelimiter, $dna)[$i];
			$dna_node_id = getDnaNodeId($dna_node);
			$pic = array_find($pics, function($p) use ($dna_node_id) {
				// echo $p['id']." - ".$dna_node_id."<br>";
				return $p['id'] == $dna_node_id;
			});
			return [
				"layer_name" => $l['name'],
				"layer_pic" => $pic,
			];
		}, $layers, array_keys($layers));
		return $mapping;
	}

	function create() {
		global $CONST, $IMG_BASE;

		// configurate all layers and pictures
		$layers = setupLayers();

		// create nft
		for ($i=1; $i <= $CONST->editionSize; $i++) { 
			// get unique nft dna
			$dna = generateDna($layers);

			// if dna exists, repeat iteration
			if (!isDnaUnique($dna)) {
				continue;
			}

			$layersToDraw = getDnaLayers($dna, $layers);

			// clear 'canvas' by setting empty image
			$output_image = $IMG_BASE;
			
			foreach ($layersToDraw as $k => $l) {
				$pic = $l['layer_pic'];
				$src = $pic['path'];

				$img = imagecreatefrompng($src);

				// header('Content-type: image/png');
				imagecopy($output_image, $img, 0, 0, 0, 0, 512, 512);
			}
			
			imagepng($output_image, "$CONST->rootPath/results/test1/$i.png");
			// imagedestroy($output_image);
		}

	}

	create();
	echo "All done!<br>";

?>