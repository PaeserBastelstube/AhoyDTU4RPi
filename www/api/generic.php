<?php
## header('Content-Type: application/json; charset=utf-8');
require_once 'generic_json.php';
print json_encode($generic_json);

if (isset($_SERVER["TERM"]) and $_SERVER["TERM"] = "xterm") {
  print ("\n");
}
?>
