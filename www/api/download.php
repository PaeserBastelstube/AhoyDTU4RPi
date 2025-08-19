<?php
include 'generic_json.php';

# siehe settings.h - Zeile 323
function saveSettings() {
	print ("save settings");
#            json[F("version")] = CONFIG_VERSION;
#            jsonNetwork(root[F("wifi")].to<JsonObject>(), true);
#            jsonNrf    (root[F("nrf")].to<JsonObject>(), true);
#            #if defined(ESP32)
#            jsonCmt    (root[F("cmt")].to<JsonObject>(), true);
#            #endif
#            jsonNtp    (root[F("ntp")].to<JsonObject>(), true);
#            jsonSun    (root[F("sun")].to<JsonObject>(), true);
#            jsonSerial (root[F("serial")].to<JsonObject>(), true);
#            jsonMqtt   (root[F("mqtt")].to<JsonObject>(), true);
#            jsonLed    (root[F("led")].to<JsonObject>(), true);
#            jsonPlugin (root[F("plugin")].to<JsonObject>(), true);
#            jsonInst   (root[F("inst")].to<JsonObject>(), true);
}

$filename = date('Y-m-d_H-i-s') . "_v" . $generic_json["generic"]["version"];

$mySwitch = NULL;
if (isset($_GET)) {
  $getKeys = array_keys($_GET);
  $mySwitch = count($getKeys) > 0 ? htmlspecialchars($getKeys[0]) : "local";
}

if ($mySwitch == "coredump") { 
	# from RestApi.h - line 378
	$filename .= "_coredump.bin";
} elseif ($mySwitch == "get_setup") { 
	# from RestApi.h - line 352
	$filename .= "_ahoy_setup.json";
} else {
	$filename .= "_local.json";
}

if (isset($_SERVER["TERM"]) and $_SERVER["TERM"] = "xterm") {
	print json_encode(["version" => $filename] + ["ahoy" => $ahoy_data], JSON_PRETTY_PRINT);
	print "\n";
} else {
	# header('Content-Type: application/json; charset=utf-8');
	# yaml_emit_file($ahoy_config["filename"], ["ahoy" => $ahoy_data]);
	header('Content-Type: application/octet-stream');
	header('Content-Description: File Transfer');
	header('Content-Disposition: attachment; filename=' . $filename);
	print json_encode(["version" => $filename] + ["ahoy" => $ahoy_data]);
}
?>
