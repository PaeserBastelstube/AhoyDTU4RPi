<?php
include 'generic_json.php';

$filename = date('Y-m-d_H-i-s') . "_v" . $generic_json["generic"]["version"];

if (isset($_GET)) {
  $getKeys = array_keys($_GET);
  $mySwitch = htmlspecialchars($getKeys[0]);
} else {
  $mySwitch = "local";
}

if ($mySwitch = "coredump") { 
  # from RestApi.h - line 378
  # 2025-05-21_19-10-16_v0.8.155_coredump.bin
  $filename .= "_coredump.bin";
} elseif ($mySwitch = "get_setup") { 
  # from RestApi.h - line 352
  # 2025-05-21_19-10-16_v0.8.155_ahoy_setup.json
  $filename .= "_ahoy_setup.json";
} else {
  header('Content-Type: application/json; charset=utf-8');
  print "local test";
}

header("Content-Description", "File Transfer");
header("Content-Type",        "application/octet-stream");
header("Content-Disposition", "attachment; filename=" . $filename);
# header('Content-Type: application/json; charset=utf-8');

print json_encode($generic_json);
if (isset($_SERVER["DISPLAY"]) and substr($_SERVER["DISPLAY"],0,10) == "localhost:") {
	print "\n";
}
?>
