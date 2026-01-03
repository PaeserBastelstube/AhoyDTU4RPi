<?PHP
################################################################################
# curl http://localhost/htdocs/middleware.php/channel.json?operation=get
# curl --header "Content-Type: application/json" --request POST --data '{"cmd":"vz_kkk","id":0,"vz_url":"http://localhost(htdocs/middleware.php"}' http://localhost/volkszaehler.php
################################################################################
require_once 'generic_json.php';

$debug_fn = "/tmp/AhoyDTU_vz.log";
function saveDebug($my_post, $s2 = "", $newfile = true){
	global $debug_fn;
	if ($newfile) {
        file_put_contents($debug_fn, "//_my_post: "	. json_encode($my_post)	. PHP_EOL, LOCK_EX);
        file_put_contents($debug_fn, "//_get :"		. json_encode($_GET)	. PHP_EOL, FILE_APPEND | LOCK_EX);
        file_put_contents($debug_fn, "//_post :"	. json_encode($_POST)	. PHP_EOL, FILE_APPEND | LOCK_EX);
        file_put_contents($debug_fn, "//_files :"	. json_encode($_FILES)	. PHP_EOL, FILE_APPEND | LOCK_EX);
        file_put_contents($debug_fn, "//_server :"	. json_encode($_SERVER)	. PHP_EOL, FILE_APPEND | LOCK_EX);
        file_put_contents($debug_fn, "//_s2 :"		. json_encode($s2)		. PHP_EOL, FILE_APPEND | LOCK_EX);
	} else {
		file_put_contents($debug_fn, "//_my_post: "	. json_encode($my_post)	. PHP_EOL, FILE_APPEND | LOCK_EX);
		file_put_contents($debug_fn, "//_s2 :"		. json_encode($s2)		. PHP_EOL, FILE_APPEND | LOCK_EX);
	}
}

##		saveDebug(json_decode(file_get_contents('php://input'), true), $ahoy_conf);

# Button "get vz-channel list" in Inverter/Edit/Advanced
$json_string_data = [];
if (isset ($_SERVER["REQUEST_METHOD"]) and $_SERVER["REQUEST_METHOD"] == "POST") {
	if (isset($_POST) and count($_POST) == 0) {
		# https://stackoverflow.com/questions/57632438/post-is-empty-on-nginx
		$json_string_data = json_decode(file_get_contents('php://input'), true);
	}
}
	saveDebug(json_decode(file_get_contents('php://input'), true), $json_string_data["vz_url"]);
if (isset($json_string_data) and count($json_string_data) > 0){
	$vz_list = file_get_contents($json_string_data["vz_url"] . "/channel.json?operation=get");
	$vz_list_json = json_decode($vz_list, JSON_PRETTY_PRINT);
	saveDebug($json_string_data, $vz_list_json, false);
	print($vz_list);
} else {
	termPrint("no input");
}
?>
