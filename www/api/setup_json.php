<?php
#
# setup_jason-php
#
#2345678901234567890123456789012345678901234567890123456789012345678901234567890
#

# include 'generic_json.php'; ## allready call in system_json 
include 'system_json.php';

$setup_json = $generic_json + [
	"system" => $system_json, 
	"mqtt" => [
		"broker"	=> isset($ahoy_data["mqtt"]["host"])     ? $ahoy_data["mqtt"]["host"] : "",
		"port"		=> isset($ahoy_data["mqtt"]["port"])     ? $ahoy_data["mqtt"]["port"] : "",
		"clientId"	=> isset($ahoy_data["mqtt"]["clientId"]) ? $ahoy_data["mqtt"]["clientId"] : "",
		"user"		=> isset($ahoy_data["mqtt"]["user"])     ? $ahoy_data["mqtt"]["user"] : "",
		"pwd"		=> isset($ahoy_data["mqtt"]["password"]) ? $ahoy_data["mqtt"]["password"] : "",
		"topic"		=> isset($ahoy_data["mqtt"]["topic"])    ? $ahoy_data["mqtt"]["topic"] : "",
		"json"		=> isset($ahoy_data["mqtt"]["asJson"])   ? $ahoy_data["mqtt"]["asJson"] : false,
		"interval"	=> isset($ahoy_data["mqtt"]["Interval"]) ? $ahoy_data["mqtt"]["Interval"] : 0, 
		"retain"	=> isset($ahoy_data["mqtt"]["Retain"])   ? $ahoy_data["mqtt"]["Retain"] : ""
	],
	"ntp" => [
		"addr" => trim(shell_exec("timedatectl show-timesync -p SystemNTPServers --value")) . "  - cannot be changed",
		"port" => "123",
		"interval" => "720"
	],
	"sun" => [
		"lat"    => isset($ahoy_data["sunset"]["latitude"])  ? $ahoy_data["sunset"]["latitude"]  : "",
		"lon"    => isset($ahoy_data["sunset"]["longitude"]) ? $ahoy_data["sunset"]["longitude"] : "",
		"offsSr" => isset($ahoy_data["sunset"]["sunOffsSr"]) ? $ahoy_data["sunset"]["sunOffsSr"] : "",
		"offsSs" => isset($ahoy_data["sunset"]["sunOffsSs"]) ? $ahoy_data["sunset"]["sunOffsSs"] : ""
	],
	"pinout" => [
		"cs"   => isset($ahoy_data["nrf"]["pinCs"])   ? $ahoy_data["nrf"]["pinCs"]   : 0xff,
		"ce"   => isset($ahoy_data["nrf"]["pinCe"])   ? $ahoy_data["nrf"]["pinCe"]   : 0xff,
		"irq"  => isset($ahoy_data["nrf"]["pinIrq"])  ? $ahoy_data["nrf"]["pinIrq"]  : 0xff,
		"sclk" => isset($ahoy_data["nrf"]["pinSclk"]) ? $ahoy_data["nrf"]["pinSclk"] : 0xff,
		"mosi" => isset($ahoy_data["nrf"]["pinMosi"]) ? $ahoy_data["nrf"]["pinMosi"] : 0xff,
		"miso" => isset($ahoy_data["nrf"]["pinMiso"]) ? $ahoy_data["nrf"]["pinMiso"] : 0xff,
		"led0" => isset($ahoy_data["ledpin"]["pinLed0"]) ? $ahoy_data["ledpin"]["pinLed0"] : 0xff,
		"led1" => isset($ahoy_data["ledpin"]["pinLed1"]) ? $ahoy_data["ledpin"]["pinLed1"] : 0xff,
		"led2" => isset($ahoy_data["ledpin"]["pinLed2"]) ? $ahoy_data["ledpin"]["pinLed2"] : 0xff,
		"led_high_active" => isset($ahoy_data["ledpin"]["pinLedHighActive"]) ? $ahoy_data["ledpin"]["pinLedHighActive"] : false,
		"led_lum" => isset($ahoy_data["ledpin"]["pinLedLum"]) ? $ahoy_data["ledpin"]["pinLedLum"] : 0
	],
	"radioCmt" => [
		"en"    => $ahoy_data["cmt"]["enabled"],
		"sclk"  => isset($ahoy_data["cmt"]["pinCmtSclk"]) ? $ahoy_data["cmt"]["pinCmtSclk"] : 0xff,
		"sdio"  => isset($ahoy_data["cmt"]["pinSdio"])    ? $ahoy_data["cmt"]["pinSdio"]    : 0xff,
		"csb"   => isset($ahoy_data["cmt"]["pinCsb"])     ? $ahoy_data["cmt"]["pinCsb"]     : 0xff,
		"fcsb"  => isset($ahoy_data["cmt"]["pinFcsb"])    ? $ahoy_data["cmt"]["pinFcsb"]    : 0xff,
		"gpio3" => isset($ahoy_data["cmt"]["pinGpio3"])   ? $ahoy_data["cmt"]["pinGpio3"]   : 0xff,
		"freq_min" => 860,
		"freq_max" => 870
	],
	"eth" => [
		"en"    => $net_wired,
		"cs"    => isset($ahoy_data["eth"]["ethCs"])   ? $ahoy_data["eth"]["ethCs"]   : 0xff,
		"sclk"  => isset($ahoy_data["eth"]["ethSclk"]) ? $ahoy_data["eth"]["ethSclk"] : 0xff,
		"miso"  => isset($ahoy_data["eth"]["ethMiso"]) ? $ahoy_data["eth"]["ethMiso"] : 0xff,
		"mosi"  => isset($ahoy_data["eth"]["ethMosi"]) ? $ahoy_data["eth"]["ethMosi"] : 0xff,
		"irq"   => isset($ahoy_data["eth"]["ethIrq"])  ? $ahoy_data["eth"]["ethIrq"]  : 0xff,
		"reset" => isset($ahoy_data["eth"]["ethRst"])  ? $ahoy_data["eth"]["ethRst"]  : 0xff
	],
	"radioNrf" => [
		"en" => $ahoy_data["nrf"]["enabled"]],
	"serial" => [
		"show_live_data" => isset($ahoy_data["logging"]["serial"]["serEn"])      ? $ahoy_data["logging"]["serial"]["serEn"] : false,
		"debug"          => isset($ahoy_data["logging"]["serial"]["serDbg"])     ? $ahoy_data["logging"]["serial"]["serDbg"] : false,
		"priv"           => isset($ahoy_data["logging"]["serial"]["priv"])       ? $ahoy_data["logging"]["serial"]["priv"] : false,
		"wholeTrace"     => isset($ahoy_data["logging"]["serial"]["wholeTrace"]) ? $ahoy_data["logging"]["serial"]["wholeTrace"] : false,
		"log2mqtt"       => isset($ahoy_data["logging"]["serial"]["log2mqtt"])   ? $ahoy_data["logging"]["serial"]["log2mqtt"] : false
	],
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
