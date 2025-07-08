<?PHP
# erase|login|save|upload|update|get_setup|coredump|factory

header('Content-Type: application/json; charset=utf-8');
include 'generic_json.php';
$filename = date('Y-m-d_H-i-s') . "_v" . $generic_json["generic"]["version"];

#file_put_contents("/tmp/AhoyDTU_asdf", "get:"    . json_encode($_GET)        . "\n", LOCK_EX);
#file_put_contents("/tmp/AhoyDTU_asdf", "post:"   . json_encode($_POST)       . "\n", FILE_APPEND | LOCK_EX);
#file_put_contents("/tmp/AhoyDTU_asdf", "server:" . json_encode($_SERVER)     . "\n", FILE_APPEND | LOCK_EX);
#file_put_contents("/tmp/AhoyDTU_asdf", "config:" . json_encode($ahoy_config) . "\n", FILE_APPEND | LOCK_EX);
## curl -d '{"key1":"value1", "key2":"value2"}' -H "Content-Type: application/json" -X POST http://localhost/upload
#print ("get: " . json_encode($_GET) . "\n");
#print ("post: " . json_encode($_POST) . "\n");
#print ("server: " . json_encode($_SERVER) . "\n");
# goto EOF;


if (isset($_SERVER["REQUEST_METHOD"]) and $_SERVER["REQUEST_METHOD"] == "POST") {
	include 'showSave.php';
	if (isset($_POST) and count($_POST) > 0) {
		#showSave(json_encode($_POST));
		showSave($_POST);

		sleep(1);
    	header("Location: index.html");
	} else {
		# https://stackoverflow.com/questions/57632438/post-is-empty-on-nginx
		# $json_string_data = json_decode(file_get_contents('php://input'), true);
		# showSave($json_string_data);

		showSave(json_decode(file_get_contents('php://input'), true));
    	header("Location: index.html");
	}

	# showSave(json_decode(file_get_contents('php://input'), true));
} elseif (isset($_GET)) {
	$getKeys = array_keys($_GET);     # read _GET command line parameter
	$getSwitch = count($getKeys) > 0 ? htmlspecialchars($getKeys[0]) : "noGetKey";

	if ($getSwitch == "erase") {
		include 'generic_json.php';
		unlink ($ahoy_config["filename"]);
		header("Location: index.html");

	} elseif ($getSwitch == "get_setup") {
		$filename .= "_ahoy_setup.json";
		# header('Content-Type: application/json; charset=utf-8');
		# yaml_emit_file($ahoy_config["filename"], ["ahoy" => $ahoy_data]);
		header('Content-Type: application/octet-stream');
		header('Content-Description: File Transfer');
		header('Content-Disposition: attachment; filename=' . $filename);
		print json_encode(["version" => $filename] + ["ahoy" => $ahoy_data]);

	} elseif ($getSwitch == "coredump") {
		$filename .= "_coredump.json";
		header('Content-Type: application/octet-stream');
		header('Content-Description: File Transfer');
		header('Content-Disposition: attachment; filename=' . $filename);
		print json_encode(["version" => $filename] + ["coredump" => $ahoy_data], JSON_PRETTY_PRINT);

	} elseif ($getSwitch == "factory") {
		include 'generic_json.php';
		$toUnlinkArray  = array($ahoy_config["filename"], "../html/colors.css", "../html/live.html");
		array_push($toUnlinkArray, $ahoy_data["WebServer"]["filepath"] . "/AhoyDTU_*.log*");
		foreach ($toUnlinkArray as $wildcardToUnlink) {
			foreach (glob($wildcardToUnlink) as $fileToUnlink) {
				if (file_exists($fileToUnlink)) unlink ($fileToUnlink);
			}
		}
		header("Location: index.html");

	} elseif ($getSwitch == "reboot") {
		include 'generic_json.php';
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
