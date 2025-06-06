<?php
include 'generic_json.php';
header('Content-Type: application/json; charset=utf-8');
print json_encode($generic_json["generic"]);
?>
