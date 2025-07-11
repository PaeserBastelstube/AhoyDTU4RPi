<?php
include'generic_json.php';

# set default values, when empty value
if (!isset($ahoy_data["sunset"]["latitude"])  or !is_numeric($ahoy_data["sunset"]["latitude"]))  $ahoy_data["sunset"]["latitude"] = 0;
if (!isset($ahoy_data["sunset"]["longitude"]) or !is_numeric($ahoy_data["sunset"]["longitude"])) $ahoy_data["sunset"]["longitude"] = 0;
if (!isset($ahoy_data["sunset"]["sunOffsSr"]) or !is_numeric($ahoy_data["sunset"]["sunOffsSr"])) $ahoy_data["sunset"]["sunOffsSr"] = 0;
if (!isset($ahoy_data["sunset"]["sunOffsSs"]) or !is_numeric($ahoy_data["sunset"]["sunOffsSs"])) $ahoy_data["sunset"]["sunOffsSs"] = 0;

$sun_info = date_sun_info(time(), $ahoy_data["sunset"]["latitude"], $ahoy_data["sunset"]["longitude"]);

# create JSON Array
$index_json = $generic_json + [
	"ts_now"     => time(),
	"ts_sunrise" => $ahoy_data["sunset"]["enabled"] ? $sun_info["sunrise"] : 0,	# timestamp of sunrise
	"ts_sunset"  => $ahoy_data["sunset"]["enabled"] ? $sun_info["sunset"]  : 0,	# timestamp of sunset
	"ts_offsSr"  => $ahoy_data["sunset"]["sunOffsSr"],							# offset in sec
	"ts_offsSs"  => $ahoy_data["sunset"]["sunOffsSs"],							# offset in sec
	"disNightComm" => $ahoy_data["sunset"]["enabled"],
	"warnings"	=> []											# Anzahl von Meldungen des Inverters
];

function readFileContent($myFN) {
	if (file_exists ($myFN)) {
		return array(
			"data" => @yaml_parse_file($myFN),
			"tsLastSuccess" => filemtime($myFN)
		);
	} 
	return array("tsLastSuccess" => NULL);
}

if (isset($ahoy_data["inverters"])) {
	foreach ($ahoy_data["inverters"] as $ii => $inv) {			# Schleife über alle Inverter in ahoy.yml
    	$pre_fn = $ahoy_data["WebServer"]["filepath"] . "/AhoyDTU_" . $inv["serial"];
		#$hw_data_yaml[$ii]     = @yaml_parse_file($pre_fn . '_HardwareInfoResponse.yml');
		#$event_data_yaml[$ii]  = @yaml_parse_file($pre_fn . '_EventsResponse.yml');
		$status_data_yaml		= readFileContent($pre_fn . '_StatusResponse.yml');

		$index_json["inverter"][$ii] = [	# fill array with current inverter data from $filepath (/tmp)
			"id" => $ii,					# zähler nummer für Inverter
			"enabled" => $inv["enabled"],	# aus Setup abfragen
			"name" => $inv["name"]			# Name des Inverters aus ahoy.yml
		];
	
		if ($status_data_yaml["tsLastSuccess"] != NULL) {
			$index_json["inverter"][$ii] += array(
				"cur_pwr" => $status_data_yaml["phases"][0]["power"],					# momentane Leistung des Inverters
				"is_avail" => true,														# Prüfung, ob letzte Meldung verfügbar ist
				"is_producing" => $index_json["ts_now"] - $status_data_yaml["tsLastSuccess"] < 60,	# Prüfung, ob letzte Meldung nicht älter als 60 Sek ist
				"ts_last_success" => $status_data_yaml["tsLastSuccess"]);				# Timestamp der letzten Meldung
		} else {
			$index_json["inverter"][$ii] += [
				"cur_pwr" => 0,					# momentane Leistung des Inverters
				"is_avail" => false,			# Prüfung, ob letzte Meldung verfügbar ist
				"is_producing" => 0,			# Prüfung, ob letzte Meldung nicht älter als 60 Sek ist
				"ts_last_success" => 0];		# Timestamp der letzten Meldung
		}
	}
}

EOF:
if (isset($_SERVER["TERM"]) and $_SERVER["TERM"] = "xterm" and
		$argv[0] == "index_json.php") {
	# header('Content-Type: application/json; charset=utf-8');
	print "/index_json:\n" . json_encode($index_json) . "\n";
}
?>
