<?php
include 'html_json.php';
if (! isset($_SERVER["TERM"]) ) {
	header('Content-Type: application/json; charset=utf-8');
}

$arrKeys = array_keys($_GET);

if (count($arrKeys) > 0) {
  $inv_id = htmlspecialchars($_GET[$arrKeys[0]]);
  $var_name = "html_" . $arrKeys[0] . "_json";
}

# print $var_name;
# echo "\n";

if (isset ($$var_name)) {
  print json_encode($$var_name);
# } else {
#  include 'api.php';
}

?>
