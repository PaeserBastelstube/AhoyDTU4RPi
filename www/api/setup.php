<?php
if (! isset($_SERVER["TERM"])) header('Content-Type: application/json; charset=utf-8');

# SAVE-Button in "setup.html"
if (isset ($_SERVER["REQUEST_METHOD"]) and $_SERVER["REQUEST_METHOD"] == "POST") {
	include 'operatingSave.php';

	# SAVE-Button in "setup.html" - except "New Inverter"
	if (isset($_POST) and count($_POST) > 0) {
		_saveSettings(json_encode($_POST));		# <-- ACHTUNG wird anscheint nicht gerufen
	} else {
		# https://stackoverflow.com/questions/57632438/post-is-empty-on-nginx
		# $json_string_data = json_decode(file_get_contents('php://input'), true);
		# operatingSave($json_string_data);

		# SAVE-Button in "New Inverter"
		saveInverter(json_decode(file_get_contents('php://input'), true));
	}
} else {										# <-- GET
  include 'setup_json.php';

  $arrKeys = array_keys($_GET);
  if (count($arrKeys) > 0) {
    $inv_id = htmlspecialchars($_GET[$arrKeys[0]]);
    $var_name = "setup_" . $arrKeys[0] . "_json";
  } else {
    $var_name = "setup_json";
  }

  if (isset ($$var_name)) print json_encode($$var_name);
  else include 'api.php';

  if (isset($_SERVER["TERM"]) and $_SERVER["TERM"] = "xterm") print ("\n");
}
?>
