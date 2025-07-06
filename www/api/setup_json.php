<?php
# include 'generic_json.php'; ## allready call in system_json 
include 'system_json.php';


if (!isset($ahoy_data["mqtt"]["host"]))		$ahoy_data["mqtt"]["host"] = "";
if (!isset($ahoy_data["mqtt"]["port"]))		$ahoy_data["mqtt"]["port"] = "";
if (!isset($ahoy_data["mqtt"]["clientId"]))	$ahoy_data["mqtt"]["clientId"] = "";
if (!isset($ahoy_data["mqtt"]["user"]))		$ahoy_data["mqtt"]["user"] = "";
if (!isset($ahoy_data["mqtt"]["password"]))	$ahoy_data["mqtt"]["password"] = "";
if (!isset($ahoy_data["mqtt"]["topic"]))	$ahoy_data["mqtt"]["topic"] = "";
if (!isset($ahoy_data["mqtt"]["asJson"]))	$ahoy_data["mqtt"]["asJson"] = false;
if (!isset($ahoy_data["mqtt"]["Interval"]))	$ahoy_data["mqtt"]["Interval"] = 0;
if (!isset($ahoy_data["mqtt"]["Retain"]))	$ahoy_data["mqtt"]["Retain"] = "";

if (!isset($ahoy_data["sunset"]["latitude"]))  {$ahoy_data["sunset"]["latitude"]  = "";}
if (!isset($ahoy_data["sunset"]["longitude"])) {$ahoy_data["sunset"]["longitude"] = "";}

if (!isset($ahoy_data["WebServer"]["serial"]["serEn"]))			$ahoy_data["WebServer"]["serial"]["serEn"] = false;
if (!isset($ahoy_data["WebServer"]["serial"]["serDbg"]))		$ahoy_data["WebServer"]["serial"]["serDbg"] = false;
if (!isset($ahoy_data["WebServer"]["serial"]["priv"]))			$ahoy_data["WebServer"]["serial"]["priv"] = false;
if (!isset($ahoy_data["WebServer"]["serial"]["wholeTrace"]))	$ahoy_data["WebServer"]["serial"]["wholeTrace"] = false;
if (!isset($ahoy_data["WebServer"]["serial"]["log2mqtt"]))		$ahoy_data["WebServer"]["serial"]["log2mqtt"] = false;

$setup_json = $generic_json + [
	"system" => $system_json, 
	"mqtt" => [
		"broker"	=> $ahoy_data["mqtt"]["host"],
		"port"		=> $ahoy_data["mqtt"]["port"],
		"clientId"	=> $ahoy_data["mqtt"]["clientId"],
		"user"		=> $ahoy_data["mqtt"]["user"],
		"pwd"		=> $ahoy_data["mqtt"]["password"],
		"topic"		=> $ahoy_data["mqtt"]["topic"],
		"json"		=> $ahoy_data["mqtt"]["asJson"],
		"interval"	=> $ahoy_data["mqtt"]["Interval"],
		"retain"	=> $ahoy_data["mqtt"]["Retain"]],
	"ntp" => [
		"addr" => trim(shell_exec("timedatectl show-timesync -p SystemNTPServers --value")) . "  - cannot be changed",
		"port" => "123",
		"interval" => "720"],
	"sun" => [
		"lat" => $ahoy_data["sunset"]["latitude"],
		"lon" => $ahoy_data["sunset"]["longitude"],
		"offsSr" => 0,
		"offsSs" => 0],
	"pinout" => [
		"cs" => 5,
		"ce" => 4,
		"irq" => 15,
		"sclk" => 18,
		"mosi" => 23,
		"miso" => 19,
		"led0" => 255,
		"led1" => 255,
		"led2" => 255,
		"led_high_active" => false,
		"led_lum" => 255],
	"radioCmt" => [
		"sclk" => 14,
		"sdio" => 12,
		"csb" => 15,
		"fcsb" => 26,
		"gpio3" => 23,
		"en" => true,
		"freq_min" => 860,
		"freq_max" => 870],
	"eth" => [
		"en"    => $net_wired,
		"cs"    => 0xff,
		"sclk"  => 0xff,
		"miso"  => 0xff,
		"mosi"  => 0xff,
		"irq"   => 0xff,
		"reset" => 0xff],
	"radioNrf" => [
		"en" => false],
	"serial" => [
		"show_live_data" => $ahoy_data["WebServer"]["serial"]["serEn"],		# serEn
		"debug"          => $ahoy_data["WebServer"]["serial"]["serDbg"],	# serDbg
		"priv"           => $ahoy_data["WebServer"]["serial"]["priv"],		# priv
		"wholeTrace"     => $ahoy_data["WebServer"]["serial"]["wholeTrace"],# wholeTrace
		"log2mqtt"       => $ahoy_data["WebServer"]["serial"]["log2mqtt"]],	# log2mqtt
	"static_ip" => [
		"ip"      => $net_ip,    # 
		"mask"    => $net_mask,  #
		"dns1"    => $net_dns1,  #
		"dns2"    => $net_dns2,  #
		"gateway" => $net_gw],   #
	"display" => [
		"disp_typ" => 0,
		"disp_pwr" => false,
		"disp_screensaver" => 0,
		"disp_rot"  => 0,
		"disp_cont" => 140,
		"disp_graph_ratio" => 0,
		"disp_graph_size"  => 2,
		"disp_clk"  => 255,
		"disp_data" => 255,
		"disp_cs"   => 255,
		"disp_dc"   => 255,
		"disp_rst"  => 255,
		"disp_bsy"  => 255,
		"pir_pin"   => 255]
];

$setup_getip_json = [ "ip" => trim(`hostname -I`) ];
$setup_networks_json = [ "success" => false, ] + $setup_getip_json;

if (isset($_SERVER["TERM"]) and $_SERVER["TERM"] = "xterm" and
	$argv[0] == "setup_json.php") {
  print "/setup_json:\n" . json_encode($setup_json) . "\n";
  print "/setup_getip_json:\n" . json_encode($setup_getip_json) . "\n";
  print "/setup_networks_json:\n" . json_encode($setup_networks_json) . "\n";
}
?>
