<?php
header('Content-Type: application/json; charset=utf-8');

$arrKeys = array_keys($_GET);  # array of keys from superglobal variable _GET
# print_r ($arrKeys);

if (count($arrKeys) > 0) {                          # _GET ist filled
  # print_r ($arrKeys);
  if ((htmlspecialchars($arrKeys[0])) == "list") {  # _GET's first key is "list"
    $inverter_var_name = "inverter_list_json";      # define name of variable
  } else { 
    $inverter_id = htmlspecialchars($_GET[$arrKeys[0]]); # _GET's first key is index number of inverter
    # print_r ($inverter_id);
    $inverter_var_name = "inverter_" . $arrKeys[0] . "_" . $inverter_id . "_json"; # define name of variable
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

# if (isset($_SERVER["TERM"]) and $_SERVER["TERM"] = "xterm") {
#   print "\n";
# }
?>
