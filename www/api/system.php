<?php
include 'system_json.php';
header('Content-Type: application/json; charset=utf-8');

$arrKeys = array_keys($_GET);
# print_r ($arrKeys);
#  print ($arrKeys[0]);
# print json_encode($_SERVER, JSON_PRETTY_PRINT);
# echo "\n";

if (count($arrKeys) > 0) {
  $inv_id = intval(htmlspecialchars($_GET[$arrKeys[0]]));
  $var_name = "system_" . $arrKeys[0] . "_json";
} else {
  $var_name = "system_json";
}

# print $var_name;
# echo "\n";

if (isset ($$var_name))
{
  print json_encode($$var_name);
} else {
  include 'api.php';
}

if (isset($_SERVER["DISPLAY"]) and substr($_SERVER["DISPLAY"],0,10) == "localhost:") {
  print "\n";
}
?>
