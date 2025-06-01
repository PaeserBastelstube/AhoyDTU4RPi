<?php
include'generic_json.php';

$live_json = $generic_json + [
	"refresh" => 5,
	"max_total_pwr" => 589.8,
	"ch0_fld_units" => ["V","A","W","Hz","","°C","kWh","Wh","W","%","var","W","°C"],
	"ch0_fld_names" => ["U_AC","I_AC","P_AC","F_AC","PF_AC","Temp","YieldTotal","YieldDay","P_DC","Efficiency","Q_AC","MaxPower","MaxTemp"],
	"fld_units" => ["V","A","W","Wh","kWh","%","W"],
	"fld_names" => ["U_DC","I_DC","P_DC","YieldDay","YieldTotal","Irradiation","MaxPower"],
	"iv" => [true,true,false,false,false,false,false,false,false,false,false,false,false,false,false,false]
];

if (isset($_SERVER["DISPLAY"]) and substr($_SERVER["DISPLAY"],0,10) == "localhost:") {
  print "live_json: " . json_encode($live_json) . "\n";
}
?>
