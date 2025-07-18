<?php
#
#2345678901234567890123456789012345678901234567890123456789012345678901234567890
#
# important to learn about "preg_match_all"
# https://www.phpliveregex.com/#tab-preg-match-all
#
include 'generic_json.php';

preg_match_all('/default via (.+) dev (.+) proto (.+) src (.+) metric/m', trim(shell_exec('ip route')), $ip_route);
$net_gw		= $ip_route[1][0]; # my router IP-Adress = Gateway-Address
$net_hw		= $ip_route[2][0]; # eth0
$net_proto	= $ip_route[3][0]; # dhcp
$net_ip		= $ip_route[4][0]; # my Raspi-IP-Address

preg_match_all('/ netmask (.+?) | ether (.+?) /m', trim(shell_exec("ifconfig $net_hw")), $ifconfig);
$net_mask  = $ifconfig[1][0];	# net_mask
$net_mac = $ifconfig[2][1];		# net_mac

$iwconfig_including_status = trim(shell_exec("iwconfig $net_hw 2>&1; echo $?"));
preg_match_all('/ESSID:"(.+)"|Signal level=([-\d]+)|^(\d+)$/m', $iwconfig_including_status, $matches);

$iwlist_including_status = trim(shell_exec("iwlist $net_hw frequency 2>&1; echo $?"));
preg_match_all('/\(Channel (\d+)\)|^(\d+)$/m', $iwlist_including_status, $match_list);

$net_wired= str_starts_with($net_hw, 'eth') ? true : false;

if (end($matches[0]) == 0) {
	$generic_json["generic"]["wifi_rssi"] = $matches[2][1] . " dBm";
    $net_ssid = $matches[1][0];
	$net_wifi_channel = $match_list[1][0];
	$net_hidd = ($matches[2][1] < 0 and $net_ssid == "") ? true : false;
} else {
	# no WiFi - wired ethernet
	$generic_rssi_str = "LAN connected";
	$net_wifi_channel = "";
	$net_ssid = "";
	$net_hidd = false;
}

# list ($net_dns1, $net_dns2) = shell_exec("cat /etc/resolv.conf | awk '/nameserver/ {print $2}'");
list ($net_dns1, $net_dns2) = explode("\n", shell_exec("cat /etc/resolv.conf | awk '/nameserver/ {print $2}'"));

# Chip Temp
$chip_temp = trim(@file_get_contents('/sys/class/thermal/thermal_zone0/temp'));
$chip_temp = intval($chip_temp,10);
$chip_temp /= 1000;

$lscpu = json_decode(shell_exec("lscpu -J"), true);
# print_r(intval($lscpu["lscpu"]));

$heap   = preg_split("/\s+/", shell_exec('df -k / | tail -1 '));
$app    = preg_split("/\s+/", shell_exec('df -k /run | tail -1 '));
$spiffs = preg_split("/\s+/", shell_exec('df -k /boot/firmware | tail -1 '));
$flash  = preg_split("/\s+/", shell_exec("free | awk '/Mem/ {print}'"));

$system_json = [
	"device_name"  => $generic_json["generic"]["host"],
	"dark_mode"    => readlink('../html/colors.css') == "../html/colorDark.css",
	"sched_reboot" => $ahoy_data["WebServer"]["system"]["sched_reboot"] ?? false,
	"pwd_set"      => $generic_json["generic"]["menu_protEn"],
	"prot_mask"    => $generic_json["generic"]["menu_mask"]
	]
    + $generic_json + [
	"radioNrf"	=> [
		"en"			=> $ahoy_data["nrf"]["enabled"] ?? false,
		"isconnected"	=> 0,
		"dataRate"		=> $ahoy_data["nrf"]["spiSpeed"] ?? 1000000,
		"irqOk"			=> isset($ahoy_data["nrf"]["spiIrq"]) ? 1 : 2,
		"sn"			=> $ahoy_data["dtu"]["serial"] ?? 0
	],
	"radioCmt" => [
		"en"          => $ahoy_data["cmt"]["enabled"] ?? false,
		"isconnected" => 1,
		"sn"          => $ahoy_data["cmt"]["serial"] ?? "",
		"irqOk"       => $ahoy_data["cmt"]["cmtIrqOk"] ?? 2
	],
	"mqtt" => [
		"enabled"   => $ahoy_data["mqtt"]["enabled"] ?? false,
		"connected" => false,
		"tx_cnt"    => 0,
		"rx_cnt"    => 0,
		"interval"  => 0
	],
	"network" => [
		"wifi_channel" => $net_wifi_channel,			# RestApi.h:807
		"wired"        => $net_wired,
		"ap_pwd"       => "esp_8266",		# Standard PW
		"ssid"         => $net_ssid,
		"hidd"         => $net_hidd,
		"mac"          => $net_mac,
		"ip"           => $net_ip
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
