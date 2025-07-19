<?PHP
# erase|login|save|upload|update|get_setup|coredump|factory

include 'generic_json.php'; 		# read config
$filename = date('Y-m-d_H-i-s') . "_v" . $generic_json["generic"]["version"];

if (isset($_SERVER["REQUEST_METHOD"]) and $_SERVER["REQUEST_METHOD"] == "POST") {
	include 'showSave.php';
	if (isset($_POST) and count($_POST) > 0) {							# SETTINGS --> SAVE SETTINGS
		saveSettings($_POST);
		sleep(1);
    	header("Location: index.html");
	} else {
		# https://stackoverflow.com/questions/57632438/post-is-empty-on-nginx
		# $json_string_data = json_decode(file_get_contents('php://input'), true);
		# showSave($json_string_data);

		importSettings(json_decode(file_get_contents('php://input'), true));   # SETTINGS - IMPORT (UPLOAD)
    	header("Location: index.html");
	}
} elseif (isset($_GET)) {
	$getKeys = array_keys($_GET);     # read _GET command line parameter
	$getSwitch = count($getKeys) > 0 ? htmlspecialchars($getKeys[0]) : "noGetKey";

	if ($getSwitch == "erase") {			# SETTINGS --> DELETE SETTINGS
		unlink ($ahoy_config["filename"]);
		header("Location: index.html");

	} elseif ($getSwitch == "get_setup") {	# SETTINGS --> EXPORT SETTINGS
		$filename .= "_ahoy_setup.json";
		# header('Content-Type: application/json; charset=utf-8');
		# yaml_emit_file($ahoy_config["filename"], ["ahoy" => $ahoy_data]);
		header('Content-Type: application/octet-stream');
		header('Content-Description: File Transfer');
		header('Content-Disposition: attachment; filename=' . $filename);
		print json_encode(["version" => $filename] + ["ahoy" => $ahoy_data]);

	} elseif ($getSwitch == "coredump") {	# SYSTEM --> DOWNLOAD COREDUMP
		$filename .= "_coredump.json";
		header('Content-Type: application/octet-stream');
		header('Content-Description: File Transfer');
		header('Content-Disposition: attachment; filename=' . $filename);
		print json_encode(["version" => $filename] + ["coredump" => $ahoy_data], JSON_PRETTY_PRINT);

	} elseif ($getSwitch == "factory") {	# SYSTEM --> FACTORY RESET
		$toUnlinkArray  = array($ahoy_config["filename"], "../html/colors.css", "../html/live.html");
		array_push($toUnlinkArray, $ahoy_data["WebServer"]["filepath"] . "/AhoyDTU_*.log*");
		foreach ($toUnlinkArray as $wildcardToUnlink) {
			foreach (glob($wildcardToUnlink) as $fileToUnlink) {
				if (file_exists($fileToUnlink)) unlink ($fileToUnlink);
			}
		}
		header("Location: index.html");

	} elseif ($getSwitch == "reboot") {		# SYSTEM --> REBOOT
		print json_encode(["reboot" => "tbd","coredump" => $ahoy_data], JSON_PRETTY_PRINT);
	}
}

if (isset($_SERVER["TERM"]) and $_SERVER["TERM"] = "xterm") {
	$filename .= "_local.json";
	print json_encode(["version" => $filename] + ["ahoy" => $ahoy_data], JSON_PRETTY_PRINT);
	print "\n";
}

EOF:
?>
