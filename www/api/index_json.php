<?php
require_once'generic_json.php';

##################################################################################
# test with ?? ==> konditionaler Operator mit NULL-Prüfung
#                  null coalescing operator 
##################################################################################
# set "sunset" default values, when empty
$ahoy_conf["sunset"]["latitude"]  = $ahoy_conf["sunset"]["latitude"]  ?? "";
$ahoy_conf["sunset"]["longitude"] = $ahoy_conf["sunset"]["longitude"] ?? "";
$ahoy_conf["sunset"]["enabled"]   = $ahoy_conf["sunset"]["enabled"]   ?? false;

if (is_numeric($ahoy_conf["sunset"]["latitude"]) and 
	is_numeric($ahoy_conf["sunset"]["longitude"]) and
	$ahoy_conf["sunset"]["enabled"]
   ) $sun_info = date_sun_info(time(), $ahoy_conf["sunset"]["latitude"], $ahoy_conf["sunset"]["longitude"]);
else $ahoy_conf["sunset"]["enabled"] = false;

# create "index" JSON Array
$index_json = $generic_json + [
	"ts_now"		=> time(),
	"ts_sunrise"	=> $sun_info["sunrise"] ?? 0,				# timestamp of sunrise
	"ts_sunset"		=> $sun_info["sunset"]  ?? 0,				# timestamp of sunset
	"ts_offsSr"		=> $ahoy_conf["sunset"]["sunOffsSr"] ?? 0,	# offset in sec
	"ts_offsSs"		=> $ahoy_conf["sunset"]["sunOffsSs"] ?? 0,	# offset in sec
	"disNightComm"	=> $ahoy_conf["sunset"]["enabled"],
	"warnings"		=> []										# number of inverter warnings
];

$ahoy_data = readOperatingData($ahoy_config["filename"]);

if (isset($ahoy_conf["inverters"])) {
	foreach ($ahoy_conf["inverters"] as $ii => $inv) {			# loop over all inverters and search for status files
		$index_json["inverter"][$ii] = [
			"id" => $ii,					# id nummer of Inverter
			"enabled" => $inv["enabled"],	# inverter status
			"name" => $inv["name"]			# inverter name
		];
	
		if (isset($ahoy_data) and $ahoy_data["ts_last_success"] != NULL) {
			if (isset($ahoy_data[$inv["serial"]]["RealTimeRunData_Debug"])) $rt_data = $ahoy_data[$inv["serial"]]["RealTimeRunData_Debug"];
			else $rt_data = array();

			$index_json["inverter"][$ii] += array(
				"cur_pwr" => $rt_data["phases"][0]["power"] ?? 0,		# current inverter power
				"is_avail" => true,										# check, if inverter online
				"is_producing" => $index_json["ts_now"] - $ahoy_data["ts_last_success"] < 60,	# check, if last data message not older then 60 sec
				"ts_last_success" => $ahoy_data["ts_last_success"]);	# Timestamp of last data file
		} else {
			$index_json["inverter"][$ii] += [
				"cur_pwr" => 0,					# current inverter power
				"is_avail" => false,			# check, if inverter online
				"is_producing" => 0,			# check, if last data message not older then 60 sec
				"ts_last_success" => 0];		# Timestamp of last data file
		}
	}
}

EOF:
if (isset($argv) and $argv[0] == "index_json.php") {
	termPrint("/ahoy_data:"	. PHP_EOL . json_encode($ahoy_data));
	termPrint("/ahoy_conf:"	. PHP_EOL . json_encode($ahoy_conf));
	termPrint("/index_json:". PHP_EOL . json_encode($index_json));
}
?>
