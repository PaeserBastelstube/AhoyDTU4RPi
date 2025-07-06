<?php
include 'inverter_json.php';			# load inverter data
if (! isset($_SERVER["TERM"]) ) {
	header('Content-Type: application/json; charset=utf-8');
}

$arrKeys = array_keys($_GET);  # array of keys from superglobal variable _GET

if (count($arrKeys) > 0) {                          # _GET ist filled
  if ((htmlspecialchars($arrKeys[0])) == "list") {  # _GET's first key is "list"
    $inverter_var_name = "inverter_list_json";      # define name of variable
  } else { 
    $inverter_id = htmlspecialchars($_GET[$arrKeys[0]]); # _GET's first key is index number of inverter
    $inverter_var_name = "inverter_" . $arrKeys[0] . "_" . $inverter_id . "_json"; # define name of variable
  }
}

if (isset ($$inverter_var_name)) {
  print json_encode($$inverter_var_name);
} else {
  if (! isset($_SERVER["TERM"])) include 'api.php';
}

#if (isset($_SERVER["TERM"]) and $_SERVER["TERM"] = "xterm") {
#	print "\n";
#}
?>
