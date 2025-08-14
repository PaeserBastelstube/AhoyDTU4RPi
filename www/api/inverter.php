<?php
if (! isset($_SERVER["TERM"]))
	header('Content-Type: application/json; charset=utf-8');

$arrKeys = array_keys($_GET);  # array of keys from superglobal variable _GET

$inverter_var_name = "inverter_list_json";			# define name of variable --> default="list"
if (isset($arrKeys) and (count($arrKeys) > 0) and (htmlspecialchars($arrKeys[0]) != "list")) {	# _GET ist filled and not "list"
	$inverter_id = htmlspecialchars($_GET[$arrKeys[0]]);										# _GET's first key is index number of inverter
	$inverter_var_name = "inverter_" . $arrKeys[0] . "_" . $inverter_id . "_json";				# change name of variable
}
include 'inverter_json.php';			# load inverter data - need variable $inverter_id if exists!!

if (isset ($$inverter_var_name)) {
	if (isset($_SERVER["TERM"]) and $_SERVER["TERM"] = "xterm") print($inverter_var_name) . "\n"; 
	print json_encode($$inverter_var_name);
} else {
	if (! isset($_SERVER["TERM"])) include 'api.php';
	else print_r($inverter_var_name);
}

if (isset($_SERVER["TERM"]) and $_SERVER["TERM"] = "xterm") {
	print "\n";
}
?>
