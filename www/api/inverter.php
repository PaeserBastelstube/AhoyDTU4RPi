<?php
header('Content-Type: application/json; charset=utf-8');

$arrKeys = array_keys($_GET);
# print_r ($arrKeys);

if (count($arrKeys) > 0) {
  # print_r ($arrKeys);
  if ((htmlspecialchars($arrKeys[0])) == "list") {
    $inverter_var_name = "inverter_list_json";
  } else { 
    $inverter_id = htmlspecialchars($_GET[$arrKeys[0]]);
    # print_r ($inverter_id);
    $inverter_var_name = "inverter_" . $arrKeys[0] . "_" . $inverter_id . "_json";
    # print_r ($inverter_var_name);
    # print "\n";
  }

  # search for AhoyDTU data
  include 'inverter_json.php';
}

if (isset ($$inverter_var_name))
{
  print json_encode($$inverter_var_name);
} else {
  include 'api.php';
}

if (isset($_SERVER["TERM"]) and $_SERVER["TERM"] = "xterm") {
  print "\n";
}
?>
