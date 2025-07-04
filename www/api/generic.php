<?php
header('Content-Type: application/json; charset=utf-8');
include 'generic_json.php';
print json_encode($generic_json["generic"]);

if (isset($_SERVER["TERM"]) and $_SERVER["TERM"] = "xterm") {
  print ("\n");
}
?>
