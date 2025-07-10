<?php
include'generic_json.php';

$live_json = $generic_json + [
	"refresh" => $ahoy_data["interval"],
	"max_total_pwr" => 0.99,
	"ch0_fld_units" => ["V","A","W","Hz","","°C","kWh","Wh","W","%","var","W","°C"],
	"ch0_fld_names" => ["U_AC","I_AC","P_AC","F_AC","PF_AC","Temp","YieldTotal","YieldDay","P_DC","Efficiency","Q_AC","MaxPower","MaxTemp"],
	"fld_units" => ["V","A","W","Wh","kWh","%","W"],
	"fld_names" => ["U_DC","I_DC","P_DC","YieldDay","YieldTotal","Irradiation","MaxPower"],
	"iv" => []
];

# loop over inverters
if (isset($ahoy_data["inverters"])) {
  for ($ii = 0; $ii < count($ahoy_data["inverters"]); $ii++) {
	# "iv" => [true,true,false,false,false,false,false,false,false,false,false,false,false,false,false,false]
    array_push($live_json["iv"], ! $ahoy_data["inverters"][$ii]["enabled"]);
  }
}

if (isset($_SERVER["TERM"]) and $_SERVER["TERM"] == "xterm" and
	$argv[0] == "live_json.php") {
	print "\live_json:\n" . json_encode($live_json) . "\n";
}
?>
