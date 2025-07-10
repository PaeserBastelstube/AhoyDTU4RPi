<?php
include'generic_json.php';

# Schleife über alle Inverter in ahoy.yml und suche nach Info-Files in {filepath}
if (isset($ahoy_data["inverters"])) {
	# for ($ii = 0; $ii < count($ahoy_data["inverters"]); $ii++) {
	foreach ($ahoy_data["inverters"] as $ii => $inv) {
    	$pre_fn = $ahoy_data["WebServer"]["filepath"] . "/AhoyDTU_" . $inv["serial"];

		#$hw_data_yaml[$ii]     = @yaml_parse_file($pre_fn . '_HardwareInfoResponse.yml');
		$status_data_yaml[$ii] = @yaml_parse_file($pre_fn . '_StatusResponse.yml');
		#$event_data_yaml[$ii]  = @yaml_parse_file($pre_fn . '_EventsResponse.yml');

		if (file_exists ($pre_fn . '_StatusResponse.yml')) {
			$ts_last_success[$ii]  = filemtime($pre_fn . '_StatusResponse.yml');
		} else {
			$ts_last_success[$ii]  = NULL;
		}

		if ($status_data_yaml[$ii]) {
			$cur_pwr[$ii] = $status_data_yaml[$ii]["phases"][0]["power"];
		} else {
			$cur_pwr[$ii] = 0;
		}
	}
}

$lat = 0; $lon = 0; $disNightComm = false;
if (isset($ahoy_data["sunset"]) and $ahoy_data["sunset"]["enabled"] == true) {
   $lat = $ahoy_data["sunset"]["latitude"]  == "" ? 0 : $ahoy_data["sunset"]["latitude"];
   $lon = $ahoy_data["sunset"]["longitude"] == "" ? 0 : $ahoy_data["sunset"]["longitude"];
   $disNightComm = $lat > 0 and $lon > 0 ? true : false;
} 
$sun_info = date_sun_info(time(), $lat, $lon);

# create JSON Array
$index_json = $generic_json + [
	"ts_now"     => time(),
	"ts_sunrise" => $disNightComm ? $sun_info["sunrise"] : 0,
	"ts_sunset"  => $disNightComm ? $sun_info["sunset"] : 0,
	"ts_offsSr"  => 0,
	"ts_offsSs"  => 0
];

if (isset($ahoy_data["inverters"])) {
  # for ($ii = 0; $ii < count($ahoy_data["inverters"]); $ii++) {
  foreach ($ahoy_data["inverters"] as $ii => $inv) {
    $index_json["inverter"][$ii] = [
		"id" => $ii,                                                          # zähler nummer für Inverter
		"enabled" => true,                                                    # aus Setup abfragen
		"name" => $inv["name"],                                               # Name des Inverters aus ahoy.yml
		"cur_pwr" => $cur_pwr[$ii],                                           # momentane Leistung des Inverters
		"is_avail" => $ts_last_success[$ii] != NULL,                          # Prüfung, ob letzte Meldung verfügbar ist
		"is_producing" => $index_json["ts_now"] - $ts_last_success[$ii] < 60, # Prüfung, ob letzte Meldung nicht älter als 60 Sek ist
		"ts_last_success" => $ts_last_success[$ii]                            # Timestamp der letzten Meldung
    ];
  }
}
$index_json["disNightComm"] = $disNightComm;                                  # Inverter werden bei Dunkelheit nicht abgefragt - siehe: ahoy.yml
$index_json["warnings"] = [];                                                 # Anzahl von Meldungen des Inverters

EOF:
if (isset($_SERVER["TERM"]) and $_SERVER["TERM"] = "xterm" and
		$argv[0] == "index_json.php") {
	# header('Content-Type: application/json; charset=utf-8');
	print "/index_json:\n" . json_encode($index_json) . "\n";
}
?>
