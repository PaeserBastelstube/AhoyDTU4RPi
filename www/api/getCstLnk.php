<?php
require_once'generic_json.php';

$cst_url = $generic_json["generic"]["cst_lnk"];
$cst_txt = $generic_json["generic"]["cst_lnk_txt"];
$getCstLnk = ["cst_lnk_txt" => $cst_txt];
$cst_tas = $generic_json["generic"]["cst_lnk_tas"] ?? false;

termPrint("cst_url: " . $cst_url);
termPrint("cst_txt: " . $cst_txt);
termPrint("Tasmota: " . $cst_tas);

if (isset($cst_url) and strlen($cst_url) > 0 and 
	isset($cst_txt) and strlen($cst_txt) > 0 and
	isset($cst_tas) and $cst_tas == true)
{
	$cst_str = file_get_contents($cst_url . "/cm?cmnd=status%208");
	if ($cst_str) {
		$cst_arr = json_decode($cst_str, true);
		// print_r($cst_arr);
		foreach (reset($cst_arr) as $ii => $value) {
			// print_r($value);
			if (gettype($value) == "array")
				$getCstLnk["cst_data"] = $value;
		}
	} else {
		termPrint("ERROR: cat get data from Link!");
	}
}

print json_encode($getCstLnk);
termPrint("");
?>
