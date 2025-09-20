<?php
#
#2345678901234567890123456789012345678901234567890123456789012345678901234567890
#
# important to learn about "preg_match_all"
# https://www.phpliveregex.com/#tab-preg-match-all
#
require_once 'generic_json.php';

# additional network configuration
$iface = $ahoy_conf["iface"][2][0];
preg_match_all('/ netmask (.+?) | ether (.+?) /m', trim(shell_exec("ifconfig $iface")), $ifconfig);
$ahoy_conf["iface"]["net_mask"] = $ifconfig[1][0];	# net_mask
$ahoy_conf["iface"]["net_mac"]  = $ifconfig[2][1];	# net_mac

if ($ahoy_conf["iface"]["wired"]){		# no WiFi - wired ethernet
	$net_wifi_channel = "";
} else {
	$iwlist_including_status = trim(shell_exec("iwlist $iface frequency 2>&1; echo $?"));
	preg_match_all('/\(Channel (\d+)\)|^(\d+)$/m', $iwlist_including_status, $channel_list);
	$net_wifi_channel = $channel_list[1][0];
}

# DNS / Nameserver
list ($net_dns1, $net_dns2) = explode("\n", shell_exec("cat /etc/resolv.conf | awk '/nameserver/ {print $2}'"));

# Chip Temp
$chip_temp = trim(@file_get_contents('/sys/class/thermal/thermal_zone0/temp'));
$chip_temp = intval($chip_temp,10);
$chip_temp /= 1000;

# CPU Information
$lscpu = json_decode(shell_exec("lscpu -J"), true);
# print_r(intval($lscpu["lscpu"]));

# Memory Information
$heap   = preg_split("/\s+/", shell_exec('df -k / | tail -1 '));
$app    = preg_split("/\s+/", shell_exec('df -k /run | tail -1 '));
$spiffs = preg_split("/\s+/", shell_exec('df -k /boot/firmware | tail -1 '));
$flash  = preg_split("/\s+/", shell_exec("free | awk '/Mem/ {print}'"));

# Define Variable
$system_json = [
	"device_name"  => $generic_json["generic"]["host"],
	"dark_mode"    => readlink('../html/colors.css') == "../html/colorDark.css",
	"sched_reboot" => $ahoy_conf["WebServer"]["system"]["sched_reboot"] ?? false,
	"pwd_set"      => $generic_json["generic"]["menu_protEn"],
	"prot_mask"    => $generic_json["generic"]["menu_mask"]
	]
    + $generic_json + [
	"radioNrf"	=> [
		"en"			=> $ahoy_conf["nrf"]["enabled"] ?? false,
		"isconnected"	=> 1,
		"dataRate"		=> $ahoy_conf["nrf"]["spiSpeed"] ?? 1000000,
		"irqOk"			=> isset($ahoy_conf["nrf"]["spiIrq"]) ? 1 : 2,
		"sn"			=> $ahoy_conf["dtu"]["serial"] ?? 0
	],
	"radioCmt" => [
		"en"          => $ahoy_conf["cmt"]["enabled"] ?? false,
		"isconnected" => 1,
		"sn"          => $ahoy_conf["cmt"]["serial"] ?? "",
		"irqOk"       => $ahoy_conf["cmt"]["cmtIrqOk"] ?? 2
	],
	"mqtt" => [
		"enabled"   => $ahoy_conf["mqtt"]["enabled"] ?? false,
		"connected" => false,
		"tx_cnt"    => 0,
		"rx_cnt"    => 0,
		"interval"  => 0
	],
	"network" => [
		"wifi_channel" => $net_wifi_channel,			# RestApi.h:807
		"wired"        => $ahoy_conf["iface"]["wired"],
		"ap_pwd"       => "esp_8266",		# Standard PW
		"ssid"         => $ahoy_conf["iface"]["essid"],
		"hidd"         => ($ahoy_conf["iface"]["rssi"] < 0 and $ahoy_conf["iface"]["essid"] == "") ? true : false,
		"mac"          => $ahoy_conf["iface"]["net_mac"],
		"ip"           => $ahoy_conf["iface"][4][0]
	],
	"chip" => [
		"cpu_freq"      => intval($lscpu["lscpu"][13]["data"]),
		"sdk"           => "v4.4.7-dirty",
		"temp_sensor_c" => $chip_temp,
		"revision"      => 1,
		"model"         => file_get_contents("/sys/firmware/devicetree/base/model"), # "ESP32-D0WDQ6",
		"cores"         => $lscpu["lscpu"][8]["data"],
		"reboot_reason" => "Software"
	],
	"memory" => [
        "flash_size"        => $flash[1] * 1024,
		"heap_frag"         => 0,     # Fragmentation
		"heap_max_free_blk" => $heap[3],     # Heap max free block (in kB)
		"heap_free"         => $heap[2],
		"par_size_app0"     => $app[1],
		"par_used_app0"     => $app[2],
		"heap_total"        => $heap[1],
		"par_size_spiffs"   => $spiffs[1],
		"par_used_spiffs"   => $spiffs[2]
	]
];

if (isset($_SERVER["TERM"]) and $_SERVER["TERM"] == "xterm" and
	$argv[0] == "system_json.php") {
	print "/system_json:\n" . json_encode($system_json) . "\n";
}
?>
