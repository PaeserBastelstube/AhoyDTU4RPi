<?PHP
# HTML:	login|update
# GET:	erase|get_setup|coredump|factory|reboot
# POST:	save|upload

include 'generic_json.php';						# read config
$filename = date('Y-m-d_H-i-s') . "_v" . $generic_json["generic"]["version"];

if (isset($_SERVER["REQUEST_METHOD"]) and $_SERVER["REQUEST_METHOD"] == "POST") {
	include 'operatingSave.php';

	if (isset($_POST) and count($_POST) > 0) {	# save		-->SETTINGS --> SAVE SETTINGS
		saveSettings($_POST);
		sleep(1);
    	header("Location: index.html");
	} else {									# upload	-->SETTINGS - IMPORT (UPLOAD)
		# https://stackoverflow.com/questions/57632438/post-is-empty-on-nginx
		# $json_string_data = json_decode(file_get_contents('php://input'), true);
		# operatingSave($json_string_data);

		importSettings(json_decode(file_get_contents('php://input'), true));
    	header("Location: index.html");
	}
} elseif (isset($_GET)) {
	$getKeys = array_keys($_GET);				# read _GET command line parameter
	$getSwitch = count($getKeys) > 0 ? htmlspecialchars($getKeys[0]) : "noGetKey";

	if ($getSwitch == "erase") {				# erase		-->SETTINGS --> DELETE SETTINGS
		unlink ($ahoy_config["filename"]);
		header("Location: index.html");

	} elseif ($getSwitch == "get_setup") {		# get_setup	 -->SETTINGS --> EXPORT SETTINGS
		$filename .= "_ahoy_setup.json";
		header('Content-Type: application/octet-stream');
		header('Content-Description: File Transfer');
		header('Content-Disposition: attachment; filename=' . $filename);
		print json_encode(["version" => $filename, "ahoy" => $ahoy_data], JSON_PRETTY_PRINT);

	} elseif ($getSwitch == "coredump") {		# coredump	-->SYSTEM --> DOWNLOAD COREDUMP
		$filename .= "_coredump.json";
		include 'system_json.php';
		include 'inverter_json.php';
		header('Content-Type: application/octet-stream');
		header('Content-Description: File Transfer');
		header('Content-Disposition: attachment; filename=' . $filename);
		print json_encode(["version" => $filename, "coredump" => $ahoy_data, "system" => $system_json, "inverter_list" => $inverter_list_json], JSON_PRETTY_PRINT);

	} elseif ($getSwitch == "factory") {		# factory	-->SYSTEM --> FACTORY RESET
		$toUnlinkArray  = array($ahoy_config["filename"], "../html/colors.css", "../html/live.html");
		# array_push($toUnlinkArray, $ahoy_data["WebServer"]["filepath"] . "/AhoyDTU_*.log*");
		array_push($toUnlinkArray, $ahoy_data["WebServer"]["filepath"] . "/AhoyDTU*");
		foreach ($toUnlinkArray as $wildcardToUnlink) {
			foreach (glob($wildcardToUnlink) as $fileToUnlink) {
				if (file_exists($fileToUnlink)) unlink ($fileToUnlink);
			}
		}
		header("Location: index.html");

	} elseif ($getSwitch == "reboot") {			# reboot	-->SYSTEM --> REBOOT
		header('Content-Type: text/plain');
		print json_encode(["reboot" => "tbd","coredump" => $ahoy_data], JSON_PRETTY_PRINT);
	}
#	print json_encode([
#                       "POST" => $_POST, "GET" => $_GET, 
#                       "cmd" => file_get_contents('php://input'), 
#                       "SERVER" => $_SERVER,
#                       "version" => $filename, "ahoy" => $ahoy_data
#           ],JSON_PRETTY_PRINT);
}

if (isset($_SERVER["TERM"]) and $_SERVER["TERM"] = "xterm") {
	$filename .= "_local.json";
	print json_encode(["version" => $filename] + ["ahoy" => $ahoy_data], JSON_PRETTY_PRINT);
	print "\n";
}

EOF:
?>
