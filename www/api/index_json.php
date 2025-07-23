<?php
include'generic_json.php';

##################################################################################
# test with ?? ==> konditionaler Operator mit NULL-Prüfung
#                  null coalescing operator 
##################################################################################
# set default values, when empty value
$ahoy_data["sunset"]["latitude"]  = $ahoy_data["sunset"]["latitude"]  ?? "";
$ahoy_data["sunset"]["longitude"] = $ahoy_data["sunset"]["longitude"] ?? "";

if (is_numeric($ahoy_data["sunset"]["latitude"]) and is_numeric($ahoy_data["sunset"]["longitude"]) and
	$ahoy_data["sunset"]["enabled"] ?? false) 
{
	$sun_info = date_sun_info(time(), $ahoy_data["sunset"]["latitude"], $ahoy_data["sunset"]["longitude"]);
} else {
	$ahoy_data["sunset"]["enabled"] = false;
}

# create JSON Array
$index_json = $generic_json + [
	"ts_now"     => time(),
	"ts_sunrise" => $sun_info["sunrise"] ?? 0,				# timestamp of sunrise
	"ts_sunset"  => $sun_info["sunset"]  ?? 0,				# timestamp of sunset
	"ts_offsSr"  => $ahoy_data["sunset"]["sunOffsSr"] ?? 0,	# offset in sec
	"ts_offsSs"  => $ahoy_data["sunset"]["sunOffsSs"] ?? 0,	# offset in sec
	"disNightComm" => $ahoy_data["sunset"]["enabled"],
	"warnings"	=> []										# number of inverter warnings
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
	foreach ($ahoy_data["inverters"] as $ii => $inv) {			# loop over all inverters and search for status files
    	$fn = $ahoy_data["WebServer"]["filepath"] . "/AhoyDTU_" . $inv["serial"] . '.yml';
		$data_yaml = readFileContent($fn);

		$index_json["inverter"][$ii] = [	# fill array with current inverter data from $filepath (/tmp)
			"id" => $ii,					# id nummer of Inverter
			"enabled" => $inv["enabled"],	# inverter status
			"name" => $inv["name"]			# inverter name
		];
	
		if (isset($data_yaml) and $data_yaml["tsLastSuccess"] != NULL) {
			if (isset($data_yaml["data"]["RealTimeRunData_Debug"])) $rt_data = $data_yaml["data"]["RealTimeRunData_Debug"];
			else $rt_data = array();

			$index_json["inverter"][$ii] += array(
				"cur_pwr" => $rt_data["phases"][0]["power"] ?? 0,				# current inverter power
				"is_avail" => true,												# check, if inverter online
				"is_producing" => $index_json["ts_now"] - $data_yaml["tsLastSuccess"] < 60,	# check, if last data message not older then 60 sec
				"ts_last_success" => $data_yaml["tsLastSuccess"]);				# Timestamp of last data file
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
if (isset($_SERVER["TERM"]) and $_SERVER["TERM"] = "xterm" and
		$argv[0] == "index_json.php") {
	# header('Content-Type: application/json; charset=utf-8');
	print "/index_json:\n" . json_encode($index_json) . "\n";
}
?>
