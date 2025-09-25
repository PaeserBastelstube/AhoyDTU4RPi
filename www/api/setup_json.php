<?php
#
# setup_jason-php
#
#2345678901234567890123456789012345678901234567890123456789012345678901234567890
#
# include 'generic_json.php'; ## allready call in system_json 
require_once 'system_json.php';

# search for SPI Interface with Chip-Enabled and CMD-Status
$ls_spi_incl_status = trim(shell_exec("ls /dev/spi* 2>&1; echo $?"));
preg_match_all('/spidev(\d+?)\.(\d+?)|^(\d+)$/m', $ls_spi_incl_status, $ls_spi);
if (end($ls_spi[0]) == 0)  							# test for return-code $?
	for ($ii=0; $ii < count($ls_spi[1])-1; $ii++) 	# loop over spi-controller
		$spi_csn[$ii] = [$ls_spi[1][$ii] * 10 + $ls_spi[2][$ii], 
			"BUS: " . strval($ls_spi[1][$ii]) . " CSN: " . strval($ls_spi[2][$ii])];

##$mqtt_incl_status = trim(shell_exec("systemctl status mosquitto; echo $?"));
##preg_match_all('/^(\d+)$/m', $mqtt_incl_status, $my_mqtt);
##if ($my_mqtt[1][0] == 0) {
##	$ahoy_conf["mqtt"]["host"]   = $ahoy_conf["mqtt"]["host"]   ?? "localhost";
##	$ahoy_conf["mqtt"]["port"]   = $ahoy_conf["mqtt"]["port"]   ?? 1883;
##	$ahoy_conf["mqtt"]["topic"]  = $ahoy_conf["mqtt"]["topic"]  ?? $ahoy_conf["dtu"]["name"] . "/" . $ahoy_conf["dtu"]["serial"];
##	$ahoy_conf["mqtt"]["asJson"] = $ahoy_conf["mqtt"]["asJson"] ?? true;
##	$ahoy_conf["mqtt"]["Retain"] = $ahoy_conf["mqtt"]["Retain"] ?? true;
##}

$setup_json = $generic_json + [
	"system" => $system_json, 
	"serial" => [
		"show_live_data" => $ahoy_conf["logging"]["serial"]["serEn"]      ?? false,
		"debug"          => $ahoy_conf["logging"]["serial"]["serDbg"]     ?? false,
		"priv"           => $ahoy_conf["logging"]["serial"]["priv"]       ?? false,
		"wholeTrace"     => $ahoy_conf["logging"]["serial"]["wholeTrace"] ?? false,
		"log2mqtt"       => $ahoy_conf["logging"]["serial"]["log2mqtt"]   ?? false
	],
	"static_ip" => [
		"ip"      => $net_ip ?? "",		# 
		"mask"    => $net_mask ?? "",	#
		"dns1"    => $net_dns1 ?? "",	#
		"dns2"    => $net_dns2 ?? "",	#
		"gateway" => $net_gw ?? ""		#
	],
	"ntp" => [
		"addr" => trim(shell_exec("timedatectl show-timesync -p SystemNTPServers --value")),
		"port" => "123",
		"interval" => "720"
	],
	"sun" => [
		"lat"    => $ahoy_conf["sunset"]["latitude"]  ?? "",
		"lon"    => $ahoy_conf["sunset"]["longitude"] ?? "",
		"offsSr" => $ahoy_conf["sunset"]["sunOffsSr"] ?? 0,
		"offsSs" => $ahoy_conf["sunset"]["sunOffsSs"] ?? 0
	],
	"mqtt" => [
		"broker"	=> $ahoy_conf["mqtt"]["host"]     ?? "",
		"port"		=> $ahoy_conf["mqtt"]["port"]     ?? "",
		"clientId"	=> $ahoy_conf["mqtt"]["clientId"] ?? "",
		"user"		=> $ahoy_conf["mqtt"]["user"]     ?? "",
		"pwd"		=> $ahoy_conf["mqtt"]["password"] ?? "",
		"topic"		=> $ahoy_conf["mqtt"]["topic"]    ?? "",
		"json"		=> $ahoy_conf["mqtt"]["asJson"]   ?? false,
		"interval"	=> $ahoy_conf["mqtt"]["Interval"] ?? 0, 
		"retain"	=> $ahoy_conf["mqtt"]["Retain"]   ?? ""
	],
	"pinout" => [
		"led0" => $ahoy_conf["ledpin"]["pinLed0"] ?? 0xff,
		"led1" => $ahoy_conf["ledpin"]["pinLed1"] ?? 0xff,
		"led2" => $ahoy_conf["ledpin"]["pinLed2"] ?? 0xff,
		"led_high_active" => $ahoy_conf["ledpin"]["pinLedHighActive"] ?? false,
		"led_lum" => $ahoy_conf["ledpin"]["pinLedLum"] ?? 0
	],
	"radioNrf" => [
		"en" => $ahoy_conf["nrf"]["enabled"],
		"csn"  => $spi_csn,		# array with available spi-bus-csn interfaces
		"spi"  => $ahoy_conf["nrf"]["spiCSN"]  ?? 0,	# selected SPI-CSN-Interface
		"speed"=> $ahoy_conf["nrf"]["spiSpeed"]?? 1000000,
		"cs"   => $ahoy_conf["nrf"]["spiCs"]   ?? 0xff,
		"irq"  => $ahoy_conf["nrf"]["spiIrq"]  ?? 0xff,
		"ce"   => $ahoy_conf["nrf"]["spiCe"]   ?? 0xff, # on Raspi, dependent on SPI_csn
		"sclk" => $ahoy_conf["nrf"]["spiSclk"] ?? 0xff, # on Raspi, dependent on SPI_csn
		"mosi" => $ahoy_conf["nrf"]["spiMosi"] ?? 0xff, # on Raspi, dependent on SPI_csn
		"miso" => $ahoy_conf["nrf"]["spiMiso"] ?? 0xff  # on Raspi, dependent on SPI_csn
	],
	"radioCmt" => [
		"en"    => $ahoy_conf["cmt"]["enabled"],
		"sclk"  => $ahoy_conf["cmt"]["pinCmtSclk"] ?? 0xff,
		"sdio"  => $ahoy_conf["cmt"]["pinSdio"]    ?? 0xff,
		"csb"   => $ahoy_conf["cmt"]["pinCsb"]     ?? 0xff,
		"fcsb"  => $ahoy_conf["cmt"]["pinFcsb"]    ?? 0xff,
		"gpio3" => $ahoy_conf["cmt"]["pinGpio3"]   ?? 0xff,
		"freq_min" => 860,
		"freq_max" => 870
	],
	"eth" => [
		"en"    => $net_wired ?? "",
		"cs"    => $ahoy_conf["eth"]["ethCs"]   ?? 0xff,
		"sclk"  => $ahoy_conf["eth"]["ethSclk"] ?? 0xff,
		"miso"  => $ahoy_conf["eth"]["ethMiso"] ?? 0xff,
		"mosi"  => $ahoy_conf["eth"]["ethMosi"] ?? 0xff,
		"irq"   => $ahoy_conf["eth"]["ethIrq"]  ?? 0xff,
		"reset" => $ahoy_conf["eth"]["ethRst"]  ?? 0xff
	],
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
$setup_json["system"]["device_name"] .= "   (cannot be change)";
$setup_json["ntp"]["addr"] .= "   (cannot be change)";

if (($net_proto??"") == "dhcp") $setup_json["static_ip"] = []; # if you are a DHCP-Client, clear static-info

$setup_getip_json = [ "ip" => trim(`hostname -I`) ];
$setup_networks_json = [ "success" => false, ] + $setup_getip_json;

if (isset($argv) and $argv[0] == "setup_json.php"){
	termPrint(
		"/setup_json:"			. PHP_EOL . json_encode($setup_json)		. PHP_EOL .
		"/setup_getip_json:"	. PHP_EOL . json_encode($setup_getip_json)	. PHP_EOL .
		"/setup_networks_json:"	. PHP_EOL . json_encode($setup_networks_json)
	);
}
?>
