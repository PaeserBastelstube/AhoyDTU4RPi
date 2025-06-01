<?php
include 'generic_json.php';

$ip_route = explode(" ", shell_exec('ip route | grep default'));
$net_gw   = $ip_route[2];
$net_hw   = $ip_route[4];
$net_ip   = $ip_route[8];
$net_mac  = trim(shell_exec("ifconfig $net_hw | awk '/ether/ {print $2}'"));
$net_mask = trim(shell_exec("ifconfig $net_hw | awk '/netmask/ {print $4}'"));

# list ($net_dns1, $net_dns2) = shell_exec("cat /etc/resolv.conf | awk '/nameserver/ {print $2}'");
list ($net_dns1, $net_dns2) = explode("\n", shell_exec("cat /etc/resolv.conf | awk '/nameserver/ {print $2}'"));

# Chip Temp
$chip_temp = trim(@file_get_contents('/sys/class/thermal/thermal_zone0/temp'));
$chip_temp = intval($chip_temp,10);
$chip_temp /= 1000;

$lscpu = json_decode(shell_exec("lscpu -J"), true);
# print_r(intval($lscpu["lscpu"]));

$system_json = [
	"device_name"  => $generic_json["generic"]["host"] . "  (cannot be changed)",
	"dark_mode"    => readlink('../html/colors.css') == "../html/colorDark.css",
	"sched_reboot" => true,
	"pwd_set"      => false,
	"prot_mask"    => 61]
    + $generic_json + [
	"chip" => [
		"cpu_freq"      => intval($lscpu["lscpu"][13]["data"]),                      # CPU Frequency
		"sdk"           => "v4.4.7-dirty",
		"temp_sensor_c" => $chip_temp,
		"revision"      => 1,
		"model"         => file_get_contents("/sys/firmware/devicetree/base/model"), # "ESP32-D0WDQ6",
		"cores"         => $lscpu["lscpu"][8]["data"],
		"reboot_reason" => "Software"],
	"radioNrf" => [
		"en"   => false],
	"mqtt" => [
		"enabled"   => false,
		"connected" => false,
		"tx_cnt"    => 0,
		"rx_cnt"    => 0,
		"interval"  => 0],
	"network" => [
		"wifi_channel" => "",
		"wired"        => true,
		"ap_pwd"       => "esp_8266",
		"ssid"         => "",
		"hidd"         => false,
		"mac"          => $net_mac,
		"ip"           => $net_ip],
	"memory" => [
		"heap_frag"         => 41,
		"heap_max_free_blk" => 94196,
		"heap_free"         => 158992,
		"par_size_app0"     => 1310720,
		"par_used_app0"     => 1300800,
		"heap_total"        => 282968,
		"par_size_spiffs"   => 1507328,
		"par_used_spiffs"   => 12288],
	"radioCmt" => [
		"en"          => true,
		"isconnected" => true,
		"sn"          => "86555594",
		"irqOk"       => 1
	]
];

# if (isset($_SERVER["DISPLAY"]) and substr($_SERVER["DISPLAY"],0,10) == "localhost:") {
if (isset($_SERVER["TERM"]) and $_SERVER["TERM"] == "xterm") {
  header('Content-Type: application/json; charset=utf-8');
  # print json_encode($_SERVER, JSON_PRETTY_PRINT);
  print_r ($system_json);
  # print_r ($chip_temp);
  # print $cpu_freq . "\n";
}
?>
