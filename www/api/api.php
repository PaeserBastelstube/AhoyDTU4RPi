<?PHP
include 'generic_json.php';

$requestScheme = isset($_SERVER['REQUEST_SCHEME']) ? $_SERVER['REQUEST_SCHEME'] : "http";
$httpHost = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : trim(shell_exec("hostname -A | awk '{print $1}'"));
$my_uri = $requestScheme . "://" . $httpHost;


$avail_endpoints = array("avail_endpoints" => [
	"generic"				=> $my_uri . "/api/generic",
	"index"					=> $my_uri . "/api/index",
	"live"					=> $my_uri . "/api/live",
	"setup"					=> $my_uri . "/api/setup",
	"setup/networks"		=> $my_uri . "/api/setup/networks",
	"setup/getip"			=> $my_uri . "/api/setup/getip",
	"system"				=> $my_uri . "/api/system",
	"powerHistory"			=> $my_uri . "/api/powerHistory",
	"powerHistoryDay"		=> $my_uri . "/api/powerHistoryDay",
	"inverter/list"			=> $my_uri . "/api/inverter/list"
]);

for ($ii = 0; $ii < count($ahoy_data["inverters"]); $ii++) {
	$avail_endpoints["avail_endpoints"] += ["inverter/id/" . $ii		 => $my_uri . "/api/inverter/id/" . $ii];
	$avail_endpoints["avail_endpoints"] += ["inverter/alarm/" . $ii	 => $my_uri . "/api/inverter/id/" . $ii];
	$avail_endpoints["avail_endpoints"] += ["inverter/version/" . $ii => $my_uri . "/api/inverter/version/" . $ii];
}

if (isset($_SERVER["TERM"]) and $_SERVER["TERM"] = "xterm") {
	print ("/api.php:\n");
} else {
	header('Content-Type: application/json; charset=utf-8');
}
print json_encode($avail_endpoints);

if (isset($_SERVER["TERM"]) and $_SERVER["TERM"] = "xterm") {
	print "\n";
}
?>
