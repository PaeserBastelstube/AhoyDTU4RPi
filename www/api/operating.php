<?PHP
# HTML:	login|update
# GET:	erase|get_setup|coredump|factory|reboot
# POST:	save|upload

require_once 'generic_json.php';						# read config
$filename = date('Y-m-d_H-i-s') . "_v" . $generic_json["generic"]["version"];

if (isset($_SERVER["REQUEST_METHOD"]) and $_SERVER["REQUEST_METHOD"] == "POST") {
	require_once 'operatingSave.php';

	if (isset($_POST) and count($_POST) > 0) {	# save		-->SETTINGS --> SAVE SETTINGS
		saveSettings($_POST);
   		header("Location: index.html");

    } elseif (isset($_GET['ctrl'])) {			# Active Power Control for inverter
		# saveDebug(json_decode(file_get_contents('php://input'), true), $ahoy_conf);
		ap_ctrl(json_decode(file_get_contents('php://input'), true));
   		header("Location: live.html");

	} else {									# upload	-->SETTINGS - IMPORT (UPLOAD)
		# https://stackoverflow.com/questions/57632438/post-is-empty-on-nginx
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
		unset($ahoy_conf["iface"]);
		print json_encode(["version" => $filename, "ahoy" => $ahoy_conf], JSON_PRETTY_PRINT);

	} elseif ($getSwitch == "factory") {		# factory	-->SYSTEM --> FACTORY RESET
		$toUnlinkArray  = array($ahoy_config["filename"], "../html/colors.css", "../html/live.html");
		# array_push($toUnlinkArray, $ahoy_conf["WebServer"]["filepath"] . "/AhoyDTU_*.log*");
		array_push($toUnlinkArray, $ahoy_conf["WebServer"]["filepath"] . "/AhoyDTU*");
		foreach ($toUnlinkArray as $wildcardToUnlink) {
			foreach (glob($wildcardToUnlink) as $fileToUnlink) {
				if (file_exists($fileToUnlink)) unlink ($fileToUnlink);
			}
		}
		header("Location: index.html");

	} elseif ($getSwitch == "reboot") {			# reboot	-->SYSTEM --> REBOOT
		include 'system_json.php';						# read config
		header('Content-Type: text/plain');
		print json_encode(["reboot" => "tbd","ahoy" => $ahoy_conf], JSON_PRETTY_PRINT);
		print"\n";
		print json_encode(["ahoy_conf" => $system_json], JSON_PRETTY_PRINT);

	} elseif ($getSwitch == "coredump") {		# coredump	-->SYSTEM --> DOWNLOAD COREDUMP
		$filename .= "_coredump.json";
		include 'system_json.php';
		include 'inverter_json.php';
		header('Content-Type: application/octet-stream');
		header('Content-Description: File Transfer');
		header('Content-Disposition: attachment; filename=' . $filename);
		print json_encode(["version" => $filename, "coredump" => $ahoy_conf, "system" => $system_json, "inverter_list" => $inverter_list_json], JSON_PRETTY_PRINT);

	} elseif (str_starts_with($getSwitch, "AhoyDTU_")) {		# -->SYSTEM --> AhoyDTU_*
		$shell_RC = shell_exec("/usr/bin/bash operatingShell.sh $getSwitch 2>&1");
		header('Content-Type: text/html');
		print("<code>shellRC : <br>" . str_replace("\n", "<br>", $shell_RC) . "</code>");

		# https://wiki.selfhtml.org/wiki/HTTP/Header/Refresh
		header( "Refresh:5; url=index.html");
	}
}

if (isset($_SERVER["TERM"]) and $_SERVER["TERM"] = "xterm") {
#	$filename .= "_local.json";
#	print json_encode(["version" => $filename] + ["ahoy" => $ahoy_conf], JSON_PRETTY_PRINT);
#	print "\n";
}

EOF:
?>
