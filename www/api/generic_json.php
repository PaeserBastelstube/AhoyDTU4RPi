<?php
# generic_json.php
# June 2025 - PaeserBastelstube - inital
#
#2345678901234567890123456789012345678901234567890123456789012345678901234567890
#
date_default_timezone_set('Europe/Berlin');
if (! isset($_SERVER["TERM"])) header('Content-Type: application/json; charset=utf-8');

$AhoyHost = trim(shell_exec("hostname -A | awk '{print $1}'"));					# hostname of raspberry
$MAC_array = explode(".", shell_exec("hostname -I | awk '{print $1}'"));		# get MAC of network interface
$dtu_serial = $MAC_array[0] * $MAC_array[1] * $MAC_array[2] * $MAC_array[3];	# def DTU-Serial from MAC

$generic_uptime_str   = @file_get_contents('/proc/uptime');						# tbd
$generic_uptime_array = explode(' ', $generic_uptime_str);						# 

$Environment = shell_exec("lsb_release -d 2>/dev/null | awk -F: '{print $2}'");
$Environment = trim($Environment); # "trim" entfernt Whitespaces am Anfang und Ende von string

# load ahoy config
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
  $ahoy_data["dtu"] = ["serial" => $dtu_serial, "name" => $AhoyHost];

  $ahoy_data["logging"] = ["filename" => "/tmp/AhoyDTU_" . strval($dtu_serial) . ".log", 
			 "level" => "INFO", "max_log_filesize" => 1000000, "max_log_files" => 1];

  $ahoy_data["sunset"]["enabled"] = false;
  $ahoy_data["nrf"]["enabled"] = false;
  $ahoy_data["cmt"]["enabled"] = false;
  $ahoy_data["mqtt"]["enabled"] = false;
  $ahoy_data["volkszaehler"]["enabled"] = false;
  $ahoy_data["influxdb"]["enabled"] = false;

  $ahoy_data["WebServer"]["filepath"] = "/tmp";
#  $ahoy_data["WebServer"]["InverterReset"]["AtMidnight"] = false;        # Reset values and YieldDay at midnight
#  $ahoy_data["WebServer"]["InverterReset"]["NotAvailable"] = false;      # Reset values when inverter status is 'not available'
#  $ahoy_data["WebServer"]["InverterReset"]["AtSunrise"] = false;         # Reset values at sunrise
#  $ahoy_data["WebServer"]["InverterReset"]["AtSunset"] = false;          # Reset values at sunset
#  $ahoy_data["WebServer"]["InverterReset"]["MaxValues"] = false;         # Include reset 'max' values
#  $ahoy_data["WebServer"]["strtWthtTm"] = false;                         # Start without time sync
#  $ahoy_data["WebServer"]["rdGrid"] = false;                             # Read Grid Profile
}

$menu_mask   = $ahoy_data["WebServer"]["system"]["prot_mask"] ?? 0;
$menu_protEn = isset($ahoy_data["WebServer"]["system"]["pwd_pwd"]) ? true : false;
$menu_prot   = $menu_protEn and $menu_mask > 0 ? true : false;
	
# create "ahoy-generic-data"
$generic_json = [
	"generic" => [
		"wifi_rssi"   => 0,										# WIFI-RSSI or LAN
		"ts_uptime"   => intval($generic_uptime_array[0]),		# system uptime
		"ts_now"      => time(),								# current time
		"version"     => "0.8.155",
		"modules"     => trim(shell_exec("uname -m")),			# "MDH-de",
		"build"       => "5feb293",
		"env"         => $Environment,							# "esp32-wroom32-de",
		"host"        => $AhoyHost,								# hostname
		"menu_prot"   => $menu_prot,										# Switch, if prot=set - true=locked - false=unlocked
		"menu_mask"   => $menu_mask,										# exp-sum of 7 switches
		"menu_protEn" => $menu_protEn,										# check, if prot-PW != "\0"
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
