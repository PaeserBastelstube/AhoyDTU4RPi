<?php
include 'live_json.php';
header('Content-Type: application/json; charset=utf-8');
print json_encode($live_json);
if (isset($_SERVER["DISPLAY"]) and substr($_SERVER["DISPLAY"],0,10) == "localhost:") {
  print "\n";
}
?>
