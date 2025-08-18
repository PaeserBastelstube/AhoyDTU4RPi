<?php
# generic_json.php
# June 2025 - PaeserBastelstube - inital
#
#2345678901234567890123456789012345678901234567890123456789012345678901234567890
#
date_default_timezone_set('Europe/Berlin');
if (! isset($_SERVER["TERM"])) header('Content-Type: application/json; charset=utf-8');

# Name of DTU
$AhoyHost = trim(shell_exec("hostname -A | awk '{print $1}'"));	# hostname of raspberry ==> Name of DTU

# load new ahoy config
$ahoy_config["filename"] = '../../ahoy/AhoyDTU.yml';
$ahoy_config["filetime"] = 0;
$ahoy_data = array();
if (file_exists($ahoy_config["filename"])) {
	$ahoy_config["filetime"] = filemtime($ahoy_config["filename"]);	# save timestamp
	$ahoy_data = @yaml_parse_file($ahoy_config["filename"]);		# read content data
	# echo 'Error: ', yaml_last_error_msg();
}

# check old ahoy configuration
$ahoy_config["old_filename"] = '../../ahoy/ahoy.yml';
if (file_exists($ahoy_config["old_filename"])) {
	# check timestamp, if newer --> read old config
	if (filemtime($ahoy_config["old_filename"]) > $ahoy_config["filetime"] or
      count($ahoy_data) == 0) {
		$ahoy_data += @yaml_parse_file($old_ahoy_config);
		# echo 'Error: ', yaml_last_error_msg();
	}
}

# check ahoy config: when no data loaded, define default values
if (count($ahoy_data) > 0) {
  $ahoy_data = $ahoy_data["ahoy"];
} else {
  if (isset($_SERVER["TERM"]) and $_SERVER["TERM"] = "xterm") {
    echo ("No AhoyDTU-configuration found - prepare standard config!\n");
  }
  $ahoy_data["interval"] = 14;
  $ahoy_data["transmit_retries"] = 4;

	# from "src/hm/Radio.h:133"
	## // the first digit is an 8 for DTU production year 2022, the rest is filled with the ESP chipID in decimal
	# mDtuSn |= 0x80000000; 
	$IP_array = explode(".", shell_exec("hostname -I | awk '{print $1}'"));			# get IP of network interface
	$dtu_serial = intval(0x80000000 + $IP_array[1] * $IP_array[2] * $IP_array[3]);	# def DTU-Serial from IP-Address
	$ahoy_data["dtu"] = ["serial" => dechex($dtu_serial), "name" => $AhoyHost];

  $ahoy_data["WebServer"]["filepath"] = "/tmp";
  $ahoy_data["logging"] = ["filename" => "/tmp/AhoyDTU_" . strval(dechex($dtu_serial)) . ".log", 
			 "level" => "INFO", "max_log_filesize" => 1000000, "max_log_files" => 1];

  $ahoy_data["sunset"]["enabled"] = false;
  $ahoy_data["nrf"]["enabled"] = false;
  $ahoy_data["cmt"]["enabled"] = false;
  $ahoy_data["mqtt"]["enabled"] = false;
  $ahoy_data["volkszaehler"]["enabled"] = false;
  $ahoy_data["influxdb"]["enabled"] = false;
}

preg_match_all('/default via (.+) dev (.+) proto (.+) src (.+) metric/m', trim(shell_exec('ip route')), $ahoy_data["iface"]);
# $ahoy_data["iface"][0][0] = default via 192.168.254.253 dev wlan0 proto dhcp src 192.168.254.55 metric 600
#                                         |xxxxx 1 xxxxx|     | 2 |       | 3|     |xxxxxx 4 xxx| 
# $ahoy_data["iface"][1][0] = gateway IP address
# $ahoy_data["iface"][2][0] = name of network interface
# $ahoy_data["iface"][3][0] = dhcp
# $ahoy_data["iface"][4][0] = system IP address (my Raspis IP)

print_r($ahoy_data);
$wifi_rssi = 0;
if (str_starts_with($ahoy_data["iface"][2][0],"wlan")) {
	preg_match_all('/Signal level=(.+)/m', trim(shell_exec('iwconfig $ahoy_data["iface"][2][0]')), $wifi_rssi_array);
	$wifi_rssi = $wifi_rssi_array[1][0];
}
$nmcli_incl_status = explode("\n", trim(shell_exec("nmcli -f type d 2>&1; echo $?")));
if (end($nmcli_incl_status) == 0) $wifi_rssi = trim($nmcli_incl_status[1]) ?? 0;

# System Uptime
$uptime_array = explode(' ', @file_get_contents('/proc/uptime'));		# 

# string of system environment
$Environment = shell_exec("lsb_release -d 2>/dev/null | awk -F: '{print $2}'");
$Environment = trim($Environment); # "trim" entfernt Whitespaces am Anfang und Ende von string

# DTU Protection
$menu_mask   = $ahoy_data["WebServer"]["system"]["prot_mask"] ?? 0;
$menu_protEn = isset($ahoy_data["WebServer"]["system"]["pwd_pwd"]) ? true : false;
$menu_prot   = $menu_protEn and $menu_mask > 0 ? true : false;

# create "ahoy-generic-data"
$generic_json = [
	"generic" => [
		"wifi_rssi"   => $wifi_rssi,					# WIFI-RSSI or LAN
		"ts_uptime"   => intval($uptime_array[0]),		# system uptime
		"ts_now"      => time(),						# current time
		"version"     => "0.8.155",
		"modules"     => trim(shell_exec("uname -m")),	# "MDH-de",
		"build"       => "5feb293",
		"env"         => $Environment,					# "esp32-wroom32-de",
		"host"        => $AhoyHost,						# hostname
		"menu_prot"   => $menu_prot,					# Switch, if prot=set - true=locked - false=unlocked
		"menu_mask"   => $menu_mask,					# exp-sum of 7 switches
		"menu_protEn" => $menu_protEn,					# check, if prot-PW != "\0"
		"cst_lnk"     => $ahoy_data["WebServer"]["generic"]["cst"]["lnk"] ?? "",	# custom 
		"cst_lnk_txt" => $ahoy_data["WebServer"]["generic"]["cst"]["txt"] ?? "",
		"region"      => $ahoy_data["WebServer"]["generic"]["region"] ?? 0,			# wo wird das benötigt
		"timezone"    => $ahoy_data["WebServer"]["generic"]["timezone"] ?? 1,	 	# wo wird das benötigt
		"esp_type"    => "RASPI"
	]
];

if (isset($_SERVER["TERM"]) and $_SERVER["TERM"] = "xterm" and
	$argv[0] == "generic_json.php") {
	print "/generic_json:\n" . json_encode($generic_json) . "\n";
	print "/ahoy_data:\n" . json_encode($ahoy_data) . "\n";
}
?>
