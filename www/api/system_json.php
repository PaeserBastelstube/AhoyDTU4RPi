<?php
include 'generic_json.php';

$ip_route = explode(" ", shell_exec('ip route | grep default'));
$net_gw   = $ip_route[2];
$net_hw   = $ip_route[4];
$net_ip   = $ip_route[8];
$net_mac  = trim(shell_exec("ifconfig $net_hw | awk '/ether/ {print $2}'"));
$net_mask = trim(shell_exec("ifconfig $net_hw | awk '/netmask/ {print $4}'"));

$net_wifi_channel = "";
$net_wired= true;
$net_ap_pwd = "esp_8266";  # standard PW
$net_ssid = "";
$net_hidd = false;

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

if (!isset($ahoy_data["WebServer"]["system"]["sched_reboot"])) $ahoy_data["WebServer"]["system"]["sched_reboot"] = false;

$system_json = [
	"device_name"  => $generic_json["generic"]["host"] . "  (cannot be changed)",
	"dark_mode"    => readlink('../html/colors.css') == "../html/colorDark.css",
	"sched_reboot" => $ahoy_data["WebServer"]["system"]["sched_reboot"],
	"pwd_set"      => "",
	"prot_mask"    => $ahoy_data["WebServer"]["system"]["prot_mask"]]      # 61]
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
		"wifi_channel" => $net_wifi_channel,
		"wired"        => $net_wired,
		"ap_pwd"       => $net_ap_pwd,
		"ssid"         => $net_ssid,
		"hidd"         => $net_hidd,
		"mac"          => $net_mac,
		"ip"           => $net_ip],
	"memory" => [
        "flash_size"        => $flash[1] * 1024,
		"heap_frag"         => 0,     # Fragmentation
		"heap_max_free_blk" => $heap[3],     # Heap max free block (in kB)
		"heap_free"         => $heap[2],
		"par_size_app0"     => $app[1],
		"par_used_app0"     => $app[2],
		"heap_total"        => $heap[1],
		"par_size_spiffs"   => $spiffs[1],
		"par_used_spiffs"   => $spiffs[2]],
	"radioCmt" => [
		"en"          => false,
		"isconnected" => false,
		"sn"          => "",
		"irqOk"       => false
	]
];

# if (isset($_SERVER["DISPLAY"]) and substr($_SERVER["DISPLAY"],0,10) == "localhost:") {
if (isset($_SERVER["TERM"]) and $_SERVER["TERM"] == "xterm") {
	# header('Content-Type: application/json; charset=utf-8');
	print "/system_json:\n" . json_encode($system_json) . "\n";
}
?>
