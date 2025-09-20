<?php
require_once 'live_json.php';
if (!isset($_SERVER["TERM"])) header('Content-Type: application/json; charset=utf-8');

print json_encode($live_json);

if (isset($_SERVER["TERM"]) and $_SERVER["TERM"] = "xterm") {
  print "\n";
}
?>
