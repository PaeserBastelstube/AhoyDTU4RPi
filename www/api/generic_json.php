<?php
$yaml_data = @yaml_parse_file('../../rpi/ahoy.yml');
if (!$yaml_data) {
  echo 'Error: ', yaml_last_error_msg();
}
$ahoy_data = $yaml_data["ahoy"];
# print (json_encode($yaml_data)) . "\n";
# print_r ($yaml_data["ahoy"]["inverters"][0]);
# print_r ($yaml_data["ahoy"]["cst"]["txt"]);
# exit();

$_generic_rssi_str = @shell_exec('iwconfig wlan0 2>&1 | grep Quality');
if (empty($generic_rssi_str)) {$generic_rssi_str =  "No such device";}
# print "rssi_str: $generic_rssi_str \n";

$generic_uptime_str   = @file_get_contents('/proc/uptime');
$generic_uptime_array = explode(' ', $generic_uptime_str);
# print "uptime: $generic_uptime_array[0]\n";

$Environment = shell_exec("lsb_release -d 2>/dev/null | awk -F: '{print $2}'");
$Environment = trim($Environment); # Die Funktion entfernt Whitespaces am Anfang und Ende von string

$generic_json = [
	"generic" => [
		"wifi_rssi"   => $generic_rssi_str,             # -73
		"ts_uptime"   => intval($generic_uptime_array[0]),      # 65458,
		"ts_now"      => time(),                        # 1746115867
		"version"     => "0.8.155",
		"modules"     => trim(shell_exec("uname -m")),  # "MDH-de",
		"build"       => "5feb293",
		"env"         => $Environment,                  # "esp32-wroom32-de",
		"host"        => trim(shell_exec("hostname -A | awk '{print $1}'")),  # `hostname`,
		"menu_prot"   => false,
		"menu_mask"   => 61,
		"menu_protEn" => false,
		"cst_lnk"     => $ahoy_data["cst"]["lnk"],
		"cst_lnk_txt" => $ahoy_data["cst"]["txt"],
		"region"      => 0,
		"timezone"    => 1,
		"esp_type"    => "RASPI"
	]
];

# print_r ($generic_json);
if (isset($_SERVER["DISPLAY"]) and substr($_SERVER["DISPLAY"],0,10) == "localhost:") {
  #header('Content-Type: application/json; charset=utf-8');
  # print_r ($generic_json);
}
?>
