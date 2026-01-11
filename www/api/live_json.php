<?php
# require_once'generic_json.php';
require_once'inverter_json.php';

$live_json = $generic_json + [
	"refresh" => $ahoy_conf["interval"],
	"max_total_pwr" => 0.99,
	"ch0_fld_units" => ["V","A","W","Hz","","°C","kWh","Wh","W","%","var","W","°C"],
	"ch0_fld_names" => ["U_AC","I_AC","P_AC","F_AC","PF_AC","Temp","YieldTotal","YieldDay","P_DC","Efficiency","Q_AC","MaxPower","MaxTemp"],
	"fld_units" => ["V","A","W","Wh","kWh","%","W"],
	"fld_names" => ["U_DC","I_DC","P_DC","YieldDay","YieldTotal","Irradiation","MaxPower"],
	"iv" => []
];

# loop over inverters
if (isset($ahoy_conf["inverters"])) {
	for ($ii = 0; $ii < $inverter_list_json["max_num_inverters"]; $ii++) {
		# "iv" => [true,true,false,false,false,false,false,false,false,false,false,false,false,false,false,false]
		array_push($live_json["iv"], $ahoy_conf["inverters"][$ii]["enabled"] ?? false);
	}
}

if (isset($argv) and $argv[0] == "live_json.php"){
	termPrint("/live_json:" . PHP_EOL . json_encode($live_json));
}
?>
