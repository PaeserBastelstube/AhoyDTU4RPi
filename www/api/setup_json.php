<?php
#
# setup_jason-php
#
#2345678901234567890123456789012345678901234567890123456789012345678901234567890
#
# include 'generic_json.php'; ## allready call in system_json 
include 'system_json.php';

# search for SPI Interface with Chip-Enabled and CMD-Status
$ls_spi_incl_status = trim(shell_exec("ls /dev/spi* 2>&1; echo $?"));
preg_match_all('/spidev(\d+?)\.(\d+?)|^(\d+)$/m', $ls_spi_incl_status, $ls_spi);

$jj = 0;
if (end($ls_spi[0]) == 0) { 							# test for return-code $?
	for ($ii=1; $ii < count($ls_spi)-1; $ii++) {		# loop over spi-controller
		$spi_CE[$jj] = [$jj, "BUS: " . strval($ls_spi[$ii][0]) . " CSN: " . strval($ls_spi[$ii][1])];
		$jj++;
	}
}

#print_r($ls_spi);
#print_r($spi_CE);
#exit;

$setup_json = $generic_json + [
	"system" => $system_json, 
	"serial" => [
		"show_live_data" => $ahoy_data["logging"]["serial"]["serEn"]      ?? false,
		"debug"          => $ahoy_data["logging"]["serial"]["serDbg"]     ?? false,
		"priv"           => $ahoy_data["logging"]["serial"]["priv"]       ?? false,
		"wholeTrace"     => $ahoy_data["logging"]["serial"]["wholeTrace"] ?? false,
		"log2mqtt"       => $ahoy_data["logging"]["serial"]["log2mqtt"]   ?? false
	],
	"static_ip" => [
		"ip"      => $net_ip,    # 
		"mask"    => $net_mask,  #
		"dns1"    => $net_dns1,  #
		"dns2"    => $net_dns2,  #
		"gateway" => $net_gw],   #
	"ntp" => [
		"addr" => trim(shell_exec("timedatectl show-timesync -p SystemNTPServers --value")),
		"port" => "123",
		"interval" => "720"
	],
	"sun" => [
		"lat"    => $ahoy_data["sunset"]["latitude"]  ?? "",
		"lon"    => $ahoy_data["sunset"]["longitude"] ?? "",
		"offsSr" => $ahoy_data["sunset"]["sunOffsSr"] ?? 0,
		"offsSs" => $ahoy_data["sunset"]["sunOffsSs"] ?? 0
	],
	"mqtt" => [
		"broker"	=> $ahoy_data["mqtt"]["host"]     ?? "",
		"port"		=> $ahoy_data["mqtt"]["port"]     ?? "",
		"clientId"	=> $ahoy_data["mqtt"]["clientId"] ?? "",
		"user"		=> $ahoy_data["mqtt"]["user"]     ?? "",
		"pwd"		=> $ahoy_data["mqtt"]["password"] ?? "",
		"topic"		=> $ahoy_data["mqtt"]["topic"]    ?? "",
		"json"		=> $ahoy_data["mqtt"]["asJson"]   ?? false,
		"interval"	=> $ahoy_data["mqtt"]["Interval"] ?? 0, 
		"retain"	=> $ahoy_data["mqtt"]["Retain"]   ?? ""
	],
	"pinout" => [
		"led0" => $ahoy_data["ledpin"]["pinLed0"] ?? 0xff,
		"led1" => $ahoy_data["ledpin"]["pinLed1"] ?? 0xff,
		"led2" => $ahoy_data["ledpin"]["pinLed2"] ?? 0xff,
		"led_high_active" => $ahoy_data["ledpin"]["pinLedHighActive"] ?? false,
		"led_lum" => $ahoy_data["ledpin"]["pinLedLum"] ?? 0
	],
	"radioNrf" => [
		"en" => $ahoy_data["nrf"]["enabled"],
		"spi"  => $spi_CE,
		"cs"   => $ahoy_data["nrf"]["spiCs"]   ?? 0xff,
		"irq"  => $ahoy_data["nrf"]["spiIrq"]  ?? 0xff,
		"ce"   => $ahoy_data["nrf"]["spiCe"]   ?? 0xff, # on Raspi, dependent on SPI_CE
		"sclk" => $ahoy_data["nrf"]["spiSclk"] ?? 0xff, # on Raspi, dependent on SPI_CE
		"mosi" => $ahoy_data["nrf"]["spiMosi"] ?? 0xff, # on Raspi, dependent on SPI_CE
		"miso" => $ahoy_data["nrf"]["spiMiso"] ?? 0xff  # on Raspi, dependent on SPI_CE
	],
	"radioCmt" => [
		"en"    => $ahoy_data["cmt"]["enabled"],
		"sclk"  => $ahoy_data["cmt"]["pinCmtSclk"] ?? 0xff,
		"sdio"  => $ahoy_data["cmt"]["pinSdio"]    ?? 0xff,
		"csb"   => $ahoy_data["cmt"]["pinCsb"]     ?? 0xff,
		"fcsb"  => $ahoy_data["cmt"]["pinFcsb"]    ?? 0xff,
		"gpio3" => $ahoy_data["cmt"]["pinGpio3"]   ?? 0xff,
		"freq_min" => 860,
		"freq_max" => 870
	],
	"eth" => [
		"en"    => $net_wired,
		"cs"    => $ahoy_data["eth"]["ethCs"]   ?? 0xff,
		"sclk"  => $ahoy_data["eth"]["ethSclk"] ?? 0xff,
		"miso"  => $ahoy_data["eth"]["ethMiso"] ?? 0xff,
		"mosi"  => $ahoy_data["eth"]["ethMosi"] ?? 0xff,
		"irq"   => $ahoy_data["eth"]["ethIrq"]  ?? 0xff,
		"reset" => $ahoy_data["eth"]["ethRst"]  ?? 0xff
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

if ($net_proto == "dhcp") $setup_json["static_ip"] = []; # if you are a DHCP-Client, clear static-info

$setup_getip_json = [ "ip" => trim(`hostname -I`) ];
$setup_networks_json = [ "success" => false, ] + $setup_getip_json;

if (isset($_SERVER["TERM"]) and $_SERVER["TERM"] = "xterm" and
	$argv[0] == "setup_json.php") {
  print "/setup_json:\n" . json_encode($setup_json) . "\n";
  print "/setup_getip_json:\n" . json_encode($setup_getip_json) . "\n";
  print "/setup_networks_json:\n" . json_encode($setup_networks_json) . "\n";
}
?>
