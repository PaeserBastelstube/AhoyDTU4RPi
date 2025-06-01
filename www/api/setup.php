<?php
header('Content-Type: application/json; charset=utf-8');
# print_r ($_SERVER);
if (isset ($_SERVER["REQUEST_METHOD"]) and $_SERVER["REQUEST_METHOD"] == "POST") {
        print_r ($_POST);
} else {
  include 'setup_json.php';

  $arrKeys = array_keys($_GET);
  # print_r ($arrKeys);
  #  print ($arrKeys[0]);
  # print json_encode($_SERVER, JSON_PRETTY_PRINT);
  # echo "\n";

  if (count($arrKeys) > 0) {
    $inv_id = htmlspecialchars($_GET[$arrKeys[0]]);
    $var_name = "setup_" . $arrKeys[0] . "_json";
  } else {
    $var_name = "setup_json";
  }

  # print $var_name;
  # echo "\n";

  if (isset ($$var_name))
  {
    print json_encode($$var_name);
  } else {
    include 'api.php';
  }

}
?>
